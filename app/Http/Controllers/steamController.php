<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Invisnik\LaravelSteamAuth\SteamAuth;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class steamController extends Controller
{
    /**
     * @var SteamAuth
     */
    private $steam;
    public function __construct(SteamAuth $steam)
    {
        parent::__construct();
        $this->steam = $steam;
    }

    public function login()
    {
        if ($this->steam->validate())
        {
            $info = $this->steam->getUserInfo();
            if (!is_null($info))
            {
                $user = User::where('steamid', $info->getSteamID64())->first();
                if (!is_null($user))
                {
                    User::where('steamid', $info->getSteamID64())->update([
                        'name' => $info->getNick(),
                        'avatar'   => $info->getProfilePictureFull(),
                        'steamid'  => $info->getSteamID64()
                    ]);
                    $user = User::where('steamid', $info->getSteamID64())->first();
                    User::login($user);
                    return redirect('/');
                }
                else
                {
                    $user = User::create([
                        'name' => $info->getNick(),
                        'avatar'   => $info->getProfilePictureFull(),
                        'steamid'  => $info->getSteamID64()
                    ]);
                    User::login($user);
                    return redirect('/');
                }
            }
        }
        else
        {
            return $this->steam->redirect();
        }
    }

    public function logout()
    {
        session_destroy();
        return redirect('/');
    }

    public function delivery()
    {
        $id = $_REQUEST['id'];
        $password = "Amina1808";
        $check = $_REQUEST['password'];
        if(!empty($id) && $password == $check){
            DB::table('winners')->where('id', $id)->update([
                'delivery' => 1
            ]);
        }
    }

    public function winner()
    {
        $winner = DB::table('winners')->select('id', 'lot_id', 'user_id', 'token', 'usersteamid', 'ticket_id', 'delivery', 'items')
            ->where('delivery', 0)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get();
        echo $winner ? substr(json_encode($winner),1,-1):'null';
    }

    public function balance()
    {
        $password = "Amina1808";
        $steamid = $_REQUEST['steamid'];
        $sum = $_REQUEST['sum'];
        $check = $_REQUEST['password'];
        if($password == $check){
            $user = User::where('steamid', $steamid)->first();
            User::where('steamid', $steamid)->update([
                'wallet' => $user->wallet + $sum
            ]);
        }
    }
}
