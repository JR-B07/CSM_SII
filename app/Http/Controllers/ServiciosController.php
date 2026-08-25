<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ServiciosController extends Controller
{
    public function index(): View
    {
        return view('web.servicios');
    }
}
