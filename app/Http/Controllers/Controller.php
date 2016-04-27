<?php

namespace App\Http\Controllers;

use App;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public $languages = ['ru','en'];

    public function __construct()
    {
        session_start();
        $this::setLanguage();
    }

    public function setLanguage()
    {
        if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }else { $language = "ru"; }

        if(in_array($language, $this->languages))
        {
            App::setLocale($language);
        }
        else if(in_array($language, array('be','uk','ky','ab','mo','et','lv')))
        {
            App::setLocale('ru');
        }else { App::setLocale('en'); }
    }
}
