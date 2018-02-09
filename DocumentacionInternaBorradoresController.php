<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DocumentacionInternaBorradoresRequest;
use Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DocumentacionInternaBorradoresController extends Controller
{
     private $route= 'documentacion-interna-borradores';
    public function __construct()
    {
        $this->middleware('auth');
        view()->share('titulo', 'Borradores');
        view()->share('current_route', $this->route);
    }


    public function create()
    {
        $sectores= \App\Http\Models\Catalogos\SectorClasificacion::lists();
        $dependencias= \App\Http\Models\Catalogos\Dependencia::lists(['id_sector'=>1]);
        $tipos_documento= \App\Http\Models\Catalogos\TipoDocumento::lists();
        $areas= \App\Http\Models\Catalogos\Area::lists(['id_dependencia'=>4]);
        $areas_cc= \App\Http\Models\Catalogos\Area::lists(['id_dependencia'=>4]);
        $curp=trim(Auth::User()->curp);

        return view('documentacion.coordinacion.borradores.interna.create', ['sectores'=>$sectores, 'areas'=>$areas, 'dependencias'=>$dependencias, 'tipos_documento'=>$tipos_documento,'areas_cc'=>$areas_cc,'curp'=>$curp]);
    }
    public function edit($id)
    {
        $resultados=\App\Http\Models\DocumentacionInternaCoordinacion::edit($id)->first();

        $sectores= \App\Http\Models\Catalogos\SectorClasificacion::lists();
        $dependencias= \App\Http\Models\Catalogos\Dependencia::lists(['id_sector'=>1]);
        $tipos_documento= \App\Http\Models\Catalogos\TipoDocumento::lists();
        $areas= \App\Http\Models\Catalogos\Area::lists(['id_dependencia'=>4]);
        $areas_cc= \App\Http\Models\Catalogos\Area::lists(['id_dependencia'=>4]);

        $resultados['destinatarios']=json_decode($resultados->destinatarios);
        $resultados['cc']=json_decode($resultados->cc);
        $curp=trim(Auth::User()->curp);
        $doctos=\App\Http\Models\ejecutiva\BorradoresCoordinacion::docs_adjuntos(['id_oficio'=>$resultados->id,'id_tipo_documento'=>$resultados->id_tipo_documento])->get();


        $lbl_remitente='';
            $area_rem= \App\Http\Models\Catalogos\Area::find($resultados->id_area_envia);
            $lbl_remitente.=$area_rem->area.'.- '.$area_rem->nombre.' '.$area_rem->ap_paterno.' '.$area_rem->ap_materno;



        $lbl_destinatarios='';
        foreach($resultados['destinatarios'] as $d){
            $area= \App\Http\Models\Catalogos\Area::find($d);
            $lbl_destinatarios.=$area->area.'.- '.$area->nombre.' '.$area->ap_paterno.' '.$area->ap_materno.', ';
        }


        return view('documentacion.coordinacion.borradores.interna.edit', ['resultados'=>$resultados,'sectores'=>$sectores, 'areas'=>$areas, 'dependencias'=>$dependencias, 'tipos_documento'=>$tipos_documento,'areas_cc'=>$areas_cc,'doctos'=>$doctos,'id'=>$id,'lbl_destinatarios'=>$lbl_destinatarios,'lbl_remitente'=>$lbl_remitente,'curp'=>$curp]);
    }

    public function store(DocumentacionInternaBorradoresRequest $request)
    {
        $var_firma=$request->input('txtsignature');
        $var_serie=$request->input('txtserie');
        $var_secuencia=$request->input('txtsecuencie');
        $var_fecha_firma=$request->input('txtsignedDate');

        $post_documento = $request->only('id','id_sector','id_dependencia','id_tipo_documento','destinatarios','ext_destinatario','ext_destinatario_cargo','asunto','cuerpo','cc','ext_cc','sended_at','datos_firma','serie_firma','secuencia_firma','fecha_firma');

        $post_historial=$request->only('id_usuario','id_documentacion','id_status');

        //Checar si es creación o actualización
        isset($post_documento['id']) ? $datos_documentos = \App\Http\Models\DocumentacionInternaCoordinacion::find($post_documento['id']) : $datos_documentos= new \App\Http\Models\DocumentacionInternaCoordinacion;

        //instancio la clase de documentacion datos oficio
        //$datos_documentos= new \App\Http\Models\DocumentacionInternaCoordinacion;

        /*if(isset($post_documento['sended_at']))
            {
                $post_documento['sended_at']= date('Y-m-d');
                $post_documento['datos_firma']= $var_firma;
                $post_documentacion['serie_firma']= $var_serie;
                $post_documentacion['secuencia_firma']= $var_secuencia;
                $post_documentacion['fecha_firma']= $var_fecha_firma;
            }*/
        $post_documento['id_area_envia']= Auth::User()->id_area;
        $post_documento['lugar']= 'TUXTLA GUTIÉRREZ, CHIAPAS';
        $post_documento['fecha']= date('Y-m-d');
        $post_documento['id_status']= 1;
        if(isset($post_documento['destinatarios']))
            {
                $post_documento['destinatarios']= json_encode($post_documento['destinatarios']);
            }
        if(isset($post_documento['cc']))
            {
                $post_documento['cc']= json_encode($post_documento['cc']);
            }
        $datos_documentos->fill($post_documento)->save();
        $id_documento= $datos_documentos->id;


        //Subir archivos
         $area= \App\Http\Models\Catalogos\Area::find(Auth::User()->id_area);
         $tipo_documento= \App\Http\Models\Catalogos\TipoDocumento::find($post_documento['id_tipo_documento']);

        if($request->hasFile('files')){

            $ruta_de_archivos='';
            if(!isset($post['id']))
            {
                $path= storage_path().'/archivos/'.\App\Http\Classes\Herramientas::NormalizaURL($area->area).'/'.date('Y').'/'.\App\Http\Classes\Herramientas::NormalizaURL($tipo_documento->nombre).'/'.$id_documento;
                $ruta_de_archivos='/archivos/'.\App\Http\Classes\Herramientas::NormalizaURL($area->area).'/'.date('Y').'/'.\App\Http\Classes\Herramientas::NormalizaURL($tipo_documento->nombre).'/'.$id_documento;
            }
            else
            {
                $path= storage_path().'/archivos/'.\App\Http\Classes\Herramientas::NormalizaURL($area->area).'/'.date('Y').'/'.\App\Http\Classes\Herramientas::NormalizaURL($tipo_documento->nombre).'/'.$id_documento;
                $ruta_de_archivos='/archivos/'.\App\Http\Classes\Herramientas::NormalizaURL($area->area).'/'.date('Y').'/'.\App\Http\Classes\Herramientas::NormalizaURL($tipo_documento->nombre).'/'.$id_documento;
            }

            $files = $request->file('files');
            $i=1;
            foreach($files as $file){
                //$fileName = "Anexo-".str_pad($i, 2, '0', STR_PAD_LEFT).".".$file->getClientOriginalExtension();
                $fileName = "Anexo-".$file->getClientOriginalName();
                $tipo_archivo=$file->getClientOriginalExtension();
                $file->move($path, $fileName);

                $documento_f= new \App\Http\Models\DocumentacionAnexos;
                $post_documentos['id_documentacion']= $id_documento;
                $post_documentos['path']= $ruta_de_archivos;
                $post_documentos['nombre']= $fileName;
                $post_documentos['tipo_archivo']= $tipo_archivo;
                $documento_f->fill($post_documentos)->save();
                $i++;
            }
        }


        //Turnar Documentación
         if(isset($post_documento['sended_at']))
         {
            $datos= \App\Http\Models\DocumentacionInternaCoordinacion::find($id_documento);
            $datos['id_status']=2;
            $datos['datos_firma']= $var_firma;
            $datos['serie_firma']= $var_serie;
            $datos['secuencia_firma']= $var_secuencia;
            $datos['fecha_firma']= $var_fecha_firma;
            //Checar Folio Actual
            if(isset($datos['id'])){
            $datos['folio']= \App\Http\Classes\Herramientas::generar_folio_borradores(['id_area_envia'=>$datos['id_area_envia'], 'id_tipo_documento'=>$datos['id_tipo_documento'], 'anio'=>date('Y'), 'sended_at'=>1,'id_documento'=>$id_documento]);
            }
            $datos->save();

            if($post_documento['sended_at']!=''){

                if(isset($post_documento['destinatarios'])){

                    $post_documento['destinatarios']= json_decode($post_documento['destinatarios']);
                    foreach($post_documento['destinatarios'] as $dd){
                        $turnar= new \App\Http\Models\DocumentacionTurnado;
                        $post_turnar['id_status']=2;
                        $post_turnar['id_documentacion']=$id_documento;
                        $post_turnar['id_area']=$dd;
                        $post_turnar['id_tipo_turnado']= 1;
                        $turnar->fill($post_turnar)->save();
                    }
                }

                 //guardo los datos en la tabla historial
                $historial= new \App\Http\Models\DocumentacionHistorial;
                $post_historial['id_usuario']=Auth::User()->id;
                $post_historial['id_documentacion']=$id_documento;
                $post_historial['id_status']=$post_turnar['id_status'];
                $historial->fill($post_historial)->save();

                //Copias
                if(isset($post_documento['cc'])){
                    $post_documento['cc']= json_decode($post_documento['cc']);
                    foreach($post_documento['cc'] as $cc){
                        $turnar= new \App\Http\Models\DocumentacionTurnado;
                        $post_turnar['id_status']=2;
                        $post_turnar['id_documentacion']=$id_documento;
                        $post_turnar['id_area']=$cc;
                        $post_turnar['id_tipo_turnado']=3;
                        $turnar->fill($post_turnar)->save();
                    }
                }

            }
        }
        else
        {
            if(!isset($post_documento['id']))
                {
                        //guardo los datos en la tabla historial
                        $historial= new \App\Http\Models\DocumentacionHistorial;
                        $post_historial['id_usuario']=Auth::User()->id;
                        $post_historial['id_documentacion']=$id_documento;
                        $post_historial['id_status']=1;
                        $historial->fill($post_historial)->save();
                }
        }

         return redirect()->route('documentacion-borradores.index')->with('success', "Los datos han sido guardados <b>satisfactoriamente</b>.");

    }

    public function eliminar_archivo($id_archivo)
    {
        $update=\App\Http\Models\DocumentacionAnexos::find($id_archivo);
        $update->delete();
        \File::delete(storage_path().$update->path.'/'.$update->nombre);
        return redirect()->back();
    }

    public function download($id){
        $anexo= \App\Http\Models\DocumentacionAnexos::find($id);
        $destinationPath= storage_path().'/'.$anexo->path.'/'.$anexo->nombre;

        if(File::exists($destinationPath)){
            return response()->download($destinationPath);
        }else{
            return redirect()->back();
        }
    }

}
