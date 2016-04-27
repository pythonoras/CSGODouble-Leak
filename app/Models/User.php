<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = array('steamid', 'name', 'avatar', 'tradeoffer', 'wallet');

    public static function login(User $user)
    {
        $_SESSION = array(
            'name'      =>  $user->name,
            'avatar'    =>  $user->avatar,
            'steamid'   =>  $user->steamid
        );
    }
}
