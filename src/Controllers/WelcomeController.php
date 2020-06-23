<?php

namespace Vectorcoder\LaravelInstaller\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Session;

class WelcomeController extends Controller
{

    /**
     * Display the installer welcome page.
     *
     * @return \Illuminate\Http\Response
     */
    public function welcome()
    {
      $val = Session::get('erorrr');
      session(['erorrr'=>$val]);
        return view('vendor.installer.welcome',['error' => 0,'msg' => '']);
    }
    public function error($var)
    {
        return view('vendor.installer.error',['error' => $var]);
    }

}
