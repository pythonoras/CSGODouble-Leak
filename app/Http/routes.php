<?php

Route::get('/', 'gameController@index');
Route::get('index', 'gameController@index');
Route::get('deposit', 'gameController@deposit');
Route::get('get_game/{id}', 'gameController@get_game');
Route::get('wallet', 'gameController@wallet');
Route::get('updateItems', 'gameController@updateItems');
Route::post('make', 'gameController@make');//начать новую игру
Route::post('bet', 'gameController@bet');//сделать ставку пользователю
Route::post('game_over', 'gameController@game_over');//окончание игры
Route::post('emitter', 'gameController@emitter');//вызвать сокет эмит любой
Route::any('withdrawal/{classid}', 'gameController@withdrawal');
Route::any('tradeoffer', 'gameController@tradeoffer');
Route::get('login', 'steamController@login');
Route::any('winner', 'steamController@winner');
Route::any('delivery', 'steamController@delivery');
Route::any('balance', 'steamController@balance');
Route::get('logout', 'steamController@logout');