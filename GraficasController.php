<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GraficasController extends Controller
{
    private $route= 'listado_graficas';
	public function __construct()
	{
        $this->middleware('auth');
        view()->share('titulo', 'listado_graficas');
        view()->share('current_route', $this->route);
	}

    //region graficas
    public function getUltimoDiaMes($elAnio,$elMes) {
     return date("d",(mktime(0,0,0,$elMes+1,1,$elAnio)-1));
    }

    public function registros_mes($anio,$mes)
    {

        $primer_dia=1;
        $ultimo_dia=$this->getUltimoDiaMes($anio,$mes);
        $fe_ini="00:00:00";
        $fe_fin="23:59:59";
        $fecha_inicial=date("Y-m-d ".$fe_ini, strtotime($anio."-".$mes."-".$primer_dia) );
        $fecha_final=date("Y-m-d ".$fe_fin, strtotime($anio."-".$mes."-".$ultimo_dia) );

        $oficios= \App\Http\Models\DocumentacionEnviadaCoordinacion::datos_grafica_barra(['rango_de'=>$fecha_inicial,'rango_a'=>$fecha_final])->get();

        $ct=count($oficios);


        for($d=1;$d<=$ultimo_dia;$d++){
            $registros[$d]=0;
        }

        foreach($oficios as $oficio){
        $diasel=intval(date("d",strtotime($oficio->created_at) ) );
        $registros[$diasel]++;
        }

        $data=array("totaldias"=>$ultimo_dia, "registrosdia" =>$registros);
        return   json_encode($data);
    }

   public function index()    {
        $anio=date("Y");
        $mes=date("m");


        $date_actual = Carbon::now();
          $date_actual = $date_actual->format('Y');
        return view('dashboard.graficas.index',['date_actual'=>$date_actual])
               ->with("anio",$anio)
               ->with("mes",$mes);
    }
 }


