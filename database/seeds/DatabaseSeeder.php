<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UserTableSeeder::class);
        $this->call('UserSeeder');
    }
}

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'steamid' => '76561197965522103',
            'name' => 'Yarus',
            'avatar' => 'https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/40/40eff60e35d209eb08cbbb63e3d1731df10b432e_full.jpg',
            'tradeoffer' => '',
            'wallet' => 0
        ]);
    }
}