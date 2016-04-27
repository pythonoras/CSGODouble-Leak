<?php

namespace App\Http\Controllers;

require './vendor/autoload.php';

use Illuminate\Http\Request;

use App;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Game;
use App\Models\Participant;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;
use Illuminate\Support\Facades\DB;
use RandomOrg\Random;
use Ixudra\Curl\Facades\Curl;
use App\Models\Item;
use GuzzleHttp;

class GameController extends Controller
{
    protected $password = "Amina1808";
    public $variants = ['red', 'zero', 'black'];
    public $user = null;
    public $red = [1,3,5,7,9,12,14];
    public $black = [2,4,6,8,10,11,13];
    public $randomApiKey = ['c1fb44db-528a-464f-8ce5-689c1e0330da', '73ca5ef5-31c6-495a-9383-a40c6d193de8', 'd03a344a-1c9f-4018-ab79-25cf5cb9cc83',
        'cf9b96fa-cf3b-414f-bb37-473608bea64f','4a1efc1e-9369-4a2c-be43-616eaec1676f'];
    public $i = 0;
    public $client = null;
    public $prices = null;

    public function __construct()
    {
        parent::__construct();//в главном контроллере устанавливается язык пользователя
        $this->client = new Client(new Version1X('http://83.220.168.33:8890'));
        if(isset($_SESSION['steamid'])) $this->user = User::where('steamid', $_SESSION['steamid'])->first();
    }

    public function index()
    {
        $items = Item::where('price', '>', 0)->orderBy('price', 'DESC')->get();
        $participants = Participant::all();
        $participants = reset($participants);
        $participants = array_reverse($participants);
        $users = User::all()->groupBy("steamid");
        $games = Game::where('active', 0)->orderBy('id', 'DESC')->limit(5)->get();
        $games = reset($games);
        $games = array_reverse($games);
        return view('content.main', ['user' => $this->user, 'users' => $users, 'participants' => $participants,
                                     'games' => $games, 'red' => $this->red, 'black' => $this->black, 'items' => $items]);
    }

    public function emitter()
    {
        $this->client->initialize();
        if($this->user != null)
        {
            $_POST['data']['steamid'] = $this->user->steamid;
            $this->client->emit($_POST['emitter'], $_POST['data']);
        }
        $this->client->close();
    }

    public function wallet()
    {
        if(isset($this->user))
        {
            echo $this->user->wallet;
        }
    }

    public function updateItems()
    {
        Item::where('id', '>', 0)->delete();
        $client = new GuzzleHttp\Client();
        $request = new GuzzleHttp\Psr7\Request('GET', 'https://api.csgofast.com/price/all');
        $promise = $client->sendAsync($request)->then(function ($response) {
            $this->prices = json_decode($response->getBody(), true);

            $client = new GuzzleHttp\Client();
            $request = new GuzzleHttp\Psr7\Request('GET', 'http://steamcommunity.com/profiles/76561198012935795/inventory/json/730/2/');
            $promise = $client->sendAsync($request)->then(function ($response) {
                $client = new GuzzleHttp\Client();
                $response = json_decode($response->getBody());
                foreach($response->rgInventory as $item)
                {
                    $request = new GuzzleHttp\Psr7\Request('GET', 'http://api.steampowered.com/ISteamEconomy/GetAssetClassInfo/v0001?key=DE1038E5F0752EFF85F1824CC3698002&format=json&language=ru&appid=730&class_count=2&classid0=0&classid1='.$item->classid);
                    $promise = $client->sendAsync($request)->then(function ($response) {
                        $response = current(json_decode($response->getBody())->result);
                        $img = "https://steamcommunity-a.akamaihd.net/economy/image/class/730/".$response->classid."/360fx360f";
                        Item::create([
                            'classid' => $response->classid,
                            'name' => $response->name,
                            'market_hash_name' => $response->market_hash_name,
                            'img' => $img
                        ]);
                    });
                    $promise->wait();
                }
            });
            $promise->wait();
        });
        $promise->wait();

        foreach($this->prices as $key => $value)
        {
            $value = $value + $value*0.03;
            Item::where('market_hash_name', $key)->update([
                'price' => ceil($value*100)//1 chip is equal 0.01$
            ]);
        }
    }

    public function withdrawal($classid)
    {
        if(isset($this->user))
        {
            $item = Item::where('classid', $classid)->first();
            if($item)
            {
                if ($this->user->wallet >= $item->price)
                {
                    $token = $this->user->tradeoffer;
                    if($token)
                    {
                        preg_match("/token=.*/", $token, $token);
                        $token = str_replace("token=", "", $token[0]);
                        User::where('steamid', $this->user->steamid)->update([
                            'wallet' => $this->user->wallet - $item->price
                        ]);
                        DB::table('winners')->insert([
                            'user_id' => $this->user->steamid,
                            'usersteamid' => $this->user->steamid,
                            'token' => $token,
                            'items' => $classid
                        ]);
                        $item->delete();
                        echo 'success';
                    }
                    else
                    {
                        echo 'notification("Ошибка", "Вы не ввели ссылку на обмен, вы можете сделать
                         это, кликнув по своему аккаунту в верхнем меню", "danger")';
                    }
                }
                else
                {
                    echo 'notification("Ошибка", "Недостаточно средств", "danger")';
                }
            }
            else
            {
                echo 'notification("Ошибка", "Предмет уже забрали, обновите страницу", "danger")';
            }
        }
    }

    public function deposit()
    {
        return redirect('https://steamcommunity.com/tradeoffer/new/?partner=173783932&token=t0kzwpMs');
    }

    public function tradeoffer()
    {
        User::where('steamid', $this->user->steamid)->update([
            'tradeoffer'  =>  urldecode($_REQUEST['link'])
        ]);
    }

    public function bet()
    {
        $variant = $_POST['variant'];
        $bet = round($_POST['bet']);
        if($this->user != null && in_array($variant,$this->variants) && $this->user->wallet >= $bet && $bet > 0)
        {
            $participant = Participant::where('steamid', $this->user->steamid);
            if($participant->count() < 3 && $bet <= 1000 && ($participant->sum('bet') + $bet) <= 1000)
            {
                $game = Game::all()->last();
                if($game->active)
                {
                    Participant::create([
                        'game' => $game->id,
                        'steamid' => $this->user->steamid,
                        'bet' => $bet,
                        'variant' => $variant
                    ]);
                    User::where('steamid', $this->user->steamid)->update([
                        'wallet'  => $this->user->wallet - $bet
                    ]);
                    echo '$("balance").html(parseInt($("balance").html()) - parseInt('.$bet.'));
                    $(".your-'.$variant.'").html(parseInt($(".your-'.$variant.'").html()) + parseInt('.$bet.'));
                    notification("Успех", "Ставка принята", "success");
                ';
                    $this->client->initialize();
                    if($this->user != null)
                    {
                        $data = [
                            'steamid' => $this->user->steamid,
                            'name' => $this->user->name,
                            'avatar' => $this->user->avatar,
                            'variant' => $variant,
                            'bet' => $bet,
                        ];
                        $this->client->emit('newBet', $data);
                    }
                    $this->client->close();
                }else{
                    echo 'notification("Ошибка", "Игра окончена", "danger");';
                }
            }else{
                echo 'notification("Ошибка", "Максимальное количество ставок за одну игру 3, максимальная ставка 1000 фишек", "danger");';
            }
        }
    }

    public function game_over()
    {
        if(isset($_POST['password']) && $_POST['password'] == $this->password)
        {
            $game = Game::all()->last();
            Game::where('active', 1)->update([
                'active' => 0
            ]);
            $win_variant = "zero";
            if(in_array($game->number,$this->red))
            {
                $win_variant = "red";
            }else if(in_array($game->number, $this->black)){
                $win_variant = "black";
            }
            echo $win_variant;
            $winners = Participant::where("variant", $win_variant)->get();
            foreach($winners as $winner)
            {
                $user = User::where('steamid', $winner->steamid)->first();
                $amount = $user->wallet + ($winner->bet * 2);
                if($win_variant == "zero"){
                    $amount = $user->wallet + ($winner->bet * 8);
                }
                User::where('steamid', $winner->steamid)->update([
                    'wallet'  => $amount
                ]);
            }
            Participant::where('id', '>', '0')->delete();
        }
    }
    public function get_game($id)
    {
        $game = Game::where('active', 0)->where('id', $id)->first();
        $response = '<form style="display: none" class="randomForm" action="https://api.random.org/verify" method="post" target="_blank">
                        <input type="hidden" name="format" value="json">';
        $response .= "<input type='hidden' name='random' value='".$game->random."''>";
        $response .= ' <input type="hidden" name="signature" value="'.$game->signature.'">
                        <input class="chip ';
        if(in_array($game->number, $this->red)) $response .= 'red';
        else if(in_array($game->number, $this->black)) $response .= 'black';
        else $response .= 'green';
        $response .= '" type="submit" value="'.$game->number.'"/></form>';
        echo $response;
    }
    public function make()
    {
        if(isset($_POST['password']) && $_POST['password'] == $this->password)
        {
            try {
                $generator = new Random($this->randomApiKey[$this->i]);
                $data = $generator->generateIntegers(1, 0, 14, false, 10, true);
                $random  = $data['result']['random'];
                $number = $random['data'][0];
                $signature = $data['result']['signature'];
            } catch (Exception $e) {
                $this->i++;
                $this->make();
                exit();
            }
            $this->i = 0;
            $last = Game::where('active', 1)->first();
            if(!isset($last))
            {
                $game = Game::create([
                    'number' => $number,
                    'random' => json_encode($random),
                    'signature' => $signature
                ]);
                if(Game::all()->count() > 10)
                {
                    Game::where("active", 0)->orderBy('id', 'ASC')->limit(Game::all()->count() - 6)->delete();
                }
                echo $game->id;
            }
            else
            {
                echo $last->id;
            }
        }
        else
        {
            return redirect('/');
        }

    }
}
