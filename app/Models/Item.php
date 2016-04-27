<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = array('classid', 'name', 'market_hash_name', 'img', 'price');
}
