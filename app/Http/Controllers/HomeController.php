<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Template routes allowed without auth
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('index');
    }

    public function root(Request $request)
    {
        // SEC-06: JANGAN merender view sembarang berdasarkan path request — itu
        // membocorkan Blade internal (layout/partial/template) ke pengguna anonim.
        // Catch-all cukup mengarahkan ke login/dashboard sesuai status & peran.
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        return auth()->user()->hasAnyRole(['Mitra', 'Mitra Jasa'])
            ? redirect()->route('mitra.dashboard')
            : redirect()->route('dashboard');
    }
}
