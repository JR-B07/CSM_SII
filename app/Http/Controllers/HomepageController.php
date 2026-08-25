<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomepageController extends Controller
{
    public function index(): View
    {
        return view('web.homepage');
    }
}
