<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TvController extends Controller
{
    public function areas()
    {
        return view('tv.areas');
    }

    public function servers()
    {
        return view('tv.servers');
    }

    public function downtime()
    {
        return view('tv.downtime');
    }
}
