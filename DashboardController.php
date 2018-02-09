<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Models\Dashboard;
use App\Http\Models\DashboardDireccion;
use App\Http\Requests;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Auth;

class DashboardController extends Controller
{
    private $route= 'dashboard';
    public function __construct()
    {
        $this->middleware('auth');
        view()->share('titulo', 'Dashboard');
        view()->share('current_route', $this->route);
    }


    public function index()
    {
        $total_pendientes=count(Dashboard::total_pendientes()->get());
        $total_turnados=count(Dashboard::total_turnados()->get());
        $total_concluidos=count(Dashboard::total_concluidos()->get());

        $total_gral=count(Dashboard::total_ingresados()->get());

        $result=\App\Http\Models\Dashboard::grafica_circular()->get();

        $data=array();
            foreach ($result as $r) {
                 $data[$r->nombre]=(int)$r->total;
            }


        return view('dashboard.index',['total_pendientes'=>$total_pendientes,'total_turnados'=>$total_turnados,'total_concluidos'=>$total_concluidos,'total_gral'=>$total_gral], compact('data'));
    }


    //********************************direcciones*********************************
    public function index_direccion()
    {
        $total_gral_dir=count(DashboardDireccion::t_gral_dir(Auth::User()->id_area)->get());
        $total_pendientes_dir=count(DashboardDireccion::t_pend_dir(Auth::User()->id_area)->get());
        $total_turnados_dir=count(DashboardDireccion::t_env_dir(Auth::User()->id_area)->get());
        $total_recibidos_dir=count(DashboardDireccion::t_recib_dir(Auth::User()->id_area)->get());

        $nuevos=0;
        $list_recibidos=DashboardDireccion::t_recib_dir_nuevos(Auth::User()->id_area,$nuevos)->get();

        return view('dashboard.index',['total_gral_dir'=>$total_gral_dir, 'total_pendientes_dir'=>$total_pendientes_dir,'total_turnados_dir'=>$total_turnados_dir,'total_recibidos_dir'=>$total_recibidos_dir,'list_recibidos'=>$list_recibidos]);
    }
}