<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Redis;

class socketController extends Controller
{
    public function index()
	{
		return view('socket');
	}
	
	public function writemessage()
	{
		return view('writemessage');
	}
	
	public function sendMessage(){
		$redis = Redis::connection();
		$redis->publish('message', Request::input('message'));
		return redirect('writemessage');
	}
}
