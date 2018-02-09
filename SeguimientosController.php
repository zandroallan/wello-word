<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Classes\clsFormatDates;
use Response;
use Auth;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Http\Models\Seguimientos;

class SeguimientosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        view()->share('titulo', 'Seguimientos');
    }
	public function seguimientos($id_oficio)
    {
        $list_seguimientos=Seguimientos::seguimientos(['id_oficio'=>$id_oficio,'oficialia'=>9])->get();

        return view('documentacion.seguimientos.index',['list_seguimientos'=>$list_seguimientos]);
    }
}