<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TvController extends Controller
{
    public function areas()
    {
        return view('tv.areas');
    }

    public function area($slug)
    {
        // Simple slug to name conversion (or exact match if possible)
        // We'll try to find an area that matches the slug case-insensitively
        $area = \App\Models\Area::where('name', 'LIKE', $slug)->firstOrFail();

        return view('tv.area', compact('area'));
    }
    public function servers()
    {
        return view('tv.servers');
    }


}
