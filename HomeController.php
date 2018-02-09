<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth::User()->hasRole(['Coordinadora Ejecutiva'])) {
            // return redirect()->route('dashboard.index');
            return redirect()->route('documentacion-recibida.index');
        }
        if(Auth::User()->hasRole(['Director'])) {
            // return redirect()->route('dashboard-direccion.index-direccion');
            return redirect()->route('direcciones-recibida.index');
        }
        if(Auth::User()->hasRole(['Oficialia'])) {
            return redirect()->route('recepcion.index');
        }
        if(Auth::User()->hasRole(['Auxiliar Coordinacion'])) {
            return redirect()->route('auxiliar-copia.index');
        }
        return view('home');
    }
}
