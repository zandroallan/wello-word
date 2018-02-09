<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\DocumentacionRecepcionRequest;
use App\Http\Models\Documentacion;
use App\Http\Models\DocumentacionRecibida;
use App\Http\Models\DocumentacionAnexos;
use App\Http\Classes\clsFormatDates;
use Auth;
use Entrust;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DocumentacionRecepcionController extends Controller
{
    private $route= 'recepcion';
    public function __construct()
    {
        $this->middleware('auth');
        view()->share('titulo', 'Recepci&oacute;n');
        view()->share('current_route', $this->route);
    }
    
    public function index()
    {
    	$resultados = Documentacion::search(['id_area_envia'=>Auth::User()->id_area])->get();
    	return view('documentacion.recepcion.index',['resultados'=>$resultados]);
    }


    public function create()
    {
        $tipos_organismos= \App\Http\Models\Catalogos\TipoOrganismo::lists();
        $tipos_documento= \App\Http\Models\Catalogos\TipoDocumento::lists();
        $sectores= \App\Http\Models\Catalogos\SectorClasificacion::lists();
        $dependencias= \App\Http\Models\Catalogos\Dependencia::lists(['id_sector'=>1]);
        $areas= \App\Http\Models\Catalogos\Area::lists(['id_dependencia'=>4]);
        $vmunicipios=\App\Http\Models\Catalogos\Municipio::queryToDataBase(7);
        $doctos="";

        //$dependencias= [0=>'Seleccionar dependencia'];        
        //$areas= [0=>'Seleccionar área'];        

        return view('documentacion.recepcion.create', [
            'tipos_organismos'=>$tipos_organismos, 
            'tipos_documento'=>$tipos_documento, 
            'areas'=>$areas, 
            'dependencias'=>$dependencias, 
            'sectores'=>$sectores, 
            'doctos'=>$doctos, 
            'tipo_turnado'=>$tipo_turnado,
            'tipo_asignacion'=>$tipo_asignacion,
            'municipios' => $vmunicipios
        ]);
    }

    public function edit($id)
    {
        $resultado = Documentacion::edit($id);

        $tipos_organismos= \App\Http\Models\Catalogos\TipoOrganismo::lists();
        $tipos_documento= \App\Http\Models\Catalogos\TipoDocumento::lists();
        $sectores= \App\Http\Models\Catalogos\SectorClasificacion::lists();
        $dependencias= \App\Http\Models\Catalogos\Dependencia::lists(['id_sector'=>$resultado->id_sector_oficio]);
        $areas= \App\Http\Models\Catalogos\Area::lists(['id_dependencia'=>$resultado->id_dependencia]);
        $doctos= \App\Http\Models\DocumentacionAnexos::buscar_anexos(['id_documentacion'=>$id])->get();
        $resultado['remitente_oficio_ext']=$resultado['remitente_oficio'];
        $resultado['destinatarios_oficio']=json_decode($resultado->destinatarios_oficio);
        $resultado['cc_oficio']=json_decode($resultado->cc_oficio);
        $resultado['fecha_vencimiento_documento']=\App\Http\Classes\clsFormatDates::shortDateFormat($resultado['fecha_vencimiento_documento'], 1);
        $resultado['fecha_oficio']= \App\Http\Classes\clsFormatDates::shortDateFormat($resultado['fecha_oficio'],1);
        $resultado['fecha_recepcion_oficio']= \App\Http\Classes\clsFormatDates::shortDateFormat($resultado['fecha_recepcion_oficio'],1);
        $vmunicipios=\App\Http\Models\Catalogos\Municipio::queryToDataBase(7);

        $lbl_destinatarios='';
        foreach($resultado['destinatarios_oficio'] as $d){
            $area= \App\Http\Models\Catalogos\Area::find($d);
            $lbl_destinatarios.=$area->area.'.- '.$area->nombre.' '.$area->ap_paterno.' '.$area->ap_materno.', ';
        }
               
        return view('documentacion.recepcion.edit', [
            'resultado'=>$resultado, 
            'tipos_organismos'=>$tipos_organismos, 
            'tipos_documento'=>$tipos_documento, 
            'areas'=>$areas, 
            'dependencias'=>$dependencias, 
            'sectores'=>$sectores, 
            'doctos'=>$doctos, 
            'lbl_destinatarios'=>$lbl_destinatarios,
            'municipios' => $vmunicipios
        ]);
    }

    public function store(DocumentacionRecepcionRequest $request)
    {
        $enviado= $request->only('sended_at');
        $post_documentacion= $request->only(
            'id', 
            'asunto', 
            'cuerpo',
            'fecha_vencimiento_documento',
            'datos_firma'
        );
        $post_datos_oficio= $request->only(
            'id_documentacion_datos_oficio', 
            'id_tipo_documento_oficio', 
            'id_sector_oficio', 
            'id_dependencia_oficio', 
            'num_oficio', 
            'fecha_oficio', 
            'lugar_oficio', 
            'fecha_recepcion_oficio', 
            'remitente_oficio', 
            'remitente_oficio_ext',
            'remitente_cargo_oficio', 
            'destinatarios_oficio', 
            'cc_oficio'
        );
    
        $vfecha_vencimiento_documento=$post_documentacion['fecha_vencimiento_documento'];
        if(isset($vfecha_vencimiento_documento)){
            $fecha_vencimiento_documento=clsFormatDates::formatDates($post_documentacion['fecha_vencimiento_documento'],0);
        }
        else {
            $fecha_vencimiento_documento=strtotime('+8 day', strtotime(date('Y-m-d')));
            $fecha_vencimiento_documento=date('Y-m-d', $fecha_vencimiento_documento);
        }   

        isset($post_documentacion['id']) ? $id=$post_documentacion['id'] : $id= 0;
        if(count(Documentacion::search(['asunto'=>$post_documentacion['asunto'], 'id'=>$id])->get())>0){
            return redirect()->back()->withErrors(['flag-error'=>"Un documento con este <b>Asunto</b> ha sido registrado previamente."])->withInput();
        }
        //Checar si es creación o actualización
        isset($post_documentacion['id']) ? $datos = Documentacion::find($post_documentacion['id']) : $datos= new Documentacion;
        isset($post_datos_oficio['id_documentacion_datos_oficio']) ? $datos_oficio = \App\Http\Models\DocumentacionDatosOficio::find($post_datos_oficio['id_documentacion_datos_oficio']) : $datos_oficio= new \App\Http\Models\DocumentacionDatosOficio;

        //Checar Folio Actual
        if(isset($enviado['sended_at'])){
            if($enviado['sended_at']!=''){
                $post_documentacion['folio']= \App\Http\Classes\Herramientas::generar_folio_oficialia(['id_area_envia'=>Auth::User()->id_area, 'id_tipo_documento'=>$post_datos_oficio['id_tipo_documento_oficio'], 'anio'=>date('Y'), 'sended_at'=>1]);
            }
        }

        $area= \App\Http\Models\Catalogos\Area::find(Auth::User()->id_area);

        //Tipo de documento
        $tipo_documento= \App\Http\Models\Catalogos\TipoDocumento::find($post_datos_oficio['id_tipo_documento_oficio']);

        //Guardar datos del oficio
        $destinatarios=[]; $copias= [];
        if(isset($post_datos_oficio['destinatarios_oficio'])){ 
            $post_datos_oficio['destinatarios_oficio']=json_encode($post_datos_oficio['destinatarios_oficio']); 
            $post_documentacion['destinatarios']= $post_datos_oficio['destinatarios_oficio']; 
        }
        if(isset($post_datos_oficio['cc_oficio'])){ 
            $post_datos_oficio['cc_oficio']=json_encode($post_datos_oficio['cc_oficio']); 
            $post_documentacion['cc']= $post_datos_oficio['cc_oficio']; 
        }        
        if($post_datos_oficio['remitente_oficio'] != 0){ 
            if(empty($post_datos_oficio['remitente_oficio_ext'])){                 
                $nombre_remitente= \App\Http\Models\Catalogos\Area::find( $post_datos_oficio['remitente_oficio'] );
                $post_datos_oficio['remitente_oficio']=$nombre_remitente->nombre.' '.$nombre_remitente->ap_paterno.' '.$nombre_remitente->ap_materno; 
            }
        }
        else {
            $post_datos_oficio['remitente_oficio']=$post_datos_oficio['remitente_oficio_ext'];
        }
        
        if($post_datos_oficio['id_dependencia_oficio'] != 4){
            $post_datos_oficio['remitente_oficio']=$post_datos_oficio['remitente_oficio_ext'];
        }
        
        $post_datos_oficio['fecha_oficio']=clsFormatDates::formatDates($post_datos_oficio['fecha_oficio'],0);
        $post_datos_oficio['fecha_recepcion_oficio']= clsFormatDates::formatDates($post_datos_oficio['fecha_recepcion_oficio'],0);
        $datos_oficio->fill($post_datos_oficio)->save();
        $id_documentacion_datos_oficio= $datos_oficio->id;

        /*
         * Datos - Documentancion
         */
        if(isset($enviado['sended_at'])){
            $post_documentacion['sended_at']= date("Y-m-d H:i:s");
        }        
        $post_documentacion['fecha_vencimiento_documento']=$fecha_vencimiento_documento;
        $post_documentacion['id_documentacion_datos_oficio']= $id_documentacion_datos_oficio;
        $post_documentacion['id_tipo_documento']= $post_datos_oficio['id_tipo_documento_oficio'];
        $post_documentacion['id_sector']= 1;
        $post_documentacion['id_dependencia']= 4;
        $post_documentacion['id_area_envia']= Auth::User()->id_area;
        $post_documentacion['lugar']= 'TUXTLA GUTIÉRREZ, CHIAPAS';
        $post_documentacion['fecha']= date('Y-m-d');
        $datos->fill($post_documentacion)->save();
        $id_documentacion=$datos->id;

        //Subir archivos
        if($request->hasFile('files')){
            $ruta_de_archivos='';
            if(!isset($post['id'])) {
                $path= storage_path().'/archivos/'.\App\Http\Classes\Herramientas::NormalizaURL($area->area).'/'.date('Y').'/'.\App\Http\Classes\Herramientas::NormalizaURL($tipo_documento->nombre).'/'.$id;
                $ruta_de_archivos='archivos/'.\App\Http\Classes\Herramientas::NormalizaURL($area->area).'/'.date('Y').'/'.\App\Http\Classes\Herramientas::NormalizaURL($tipo_documento->nombre).'/'.$id;
            }
            else {
                $path= storage_path().'/archivos/'.\App\Http\Classes\Herramientas::NormalizaURL($area->area).'/'.date('Y').'/'.\App\Http\Classes\Herramientas::NormalizaURL($tipo_documento->nombre).'/'.$id;
                $ruta_de_archivos='archivos/'.\App\Http\Classes\Herramientas::NormalizaURL($area->area).'/'.date('Y').'/'.\App\Http\Classes\Herramientas::NormalizaURL($tipo_documento->nombre).'/'.$id;
            }

            $files = $request->file('files');
            $i=1;
            foreach($files as $file){
                //$fileName = "Anexo-".str_pad($i, 2, '0', STR_PAD_LEFT).".".$file->getClientOriginalExtension();
                $fileName = "Anexo-".$file->getClientOriginalName();
                $tipo_archivo=$file->getClientOriginalExtension();
                $file->move($path, $fileName);
                $documento_f= new \App\Http\Models\DocumentacionAnexos;
                $post_documentos['id_documentacion']= $id_documentacion;
                $post_documentos['path']= $ruta_de_archivos;
                $post_documentos['nombre']= $fileName;
                $post_documentos['tipo_archivo']= $tipo_archivo;
                $documento_f->fill($post_documentos)->save();
                $i++;
            }
        }
        //Turnar Documentación
        $a=1;
        if(isset($enviado['sended_at'])){
            if($enviado['sended_at']!=''){
                if(isset($post_documentacion['destinatarios'])){
                    $post_documentacion['destinatarios']= json_decode($post_documentacion['destinatarios']);
                    foreach($post_documentacion['destinatarios'] as $vdestinatarios){
                        $turnar= new \App\Http\Models\DocumentacionTurnado;
                        $post_turnar['id_status']=2;
                        $post_turnar['id_documentacion']=$id_documentacion;
                        $post_turnar['id_area']=$vdestinatarios;
                        $post_turnar['id_tipo_turnado']= 1;
                        $turnar->fill($post_turnar)->save();
                        
                        if($vdestinatarios == 1){
                            $vflDocumentacion_historial=new \App\Http\Models\DocumentacionHistorial;
                            $vdocumentacion_historial['id_usuario']=Auth::User()->id;
                            $vdocumentacion_historial['id_documentacion']=$id_documentacion;
                            $vdocumentacion_historial['id_area_envia']=Auth::User()->id_area;                            
                            $vdocumentacion_historial['id_area_turnado']=$vdestinatarios;
                            $vdocumentacion_historial['id_status']=1;   
                            $vdocumentacion_historial['fecha_vencimiento']=$fecha_vencimiento_documento;             
                            $vflDocumentacion_historial->fill($vdocumentacion_historial)->save();                            
                        }
                    }
                }
                //Copias
                if(isset($post_documentacion['cc'])){
                    $post_documentacion['cc']= json_decode($post_documentacion['cc']);
                    foreach($post_documentacion['cc'] as $cc){
                        $turnar= new \App\Http\Models\DocumentacionTurnado;
                        $post_turnar['id_status']=2;
                        $post_turnar['id_documentacion']=$id_documentacion;
                        $post_turnar['id_area']=$cc;
                        $post_turnar['id_tipo_turnado']=3;
                        $turnar->fill($post_turnar)->save();
                    }
                }
            }
        }

        if(isset($post['id'])){
            return redirect()->back()->with('success', "Los datos han sido actualizados <b>satisfactoriamente</b>.");
        }
        else{
           return redirect()->route('recepcion.index')->with('success', "Los datos han sido <b>guardados satisfactoriamente</b>.");
        }
        //Fin Store
    }

    public function destroy($id)
    {
        if(!Entrust::hasRole(['Superadministrador', 'Oficialia'])){
            return redirect()->route('dashboard.index');
        }
        $datos = Documentacion::find($id);
        $id_documentacion_datos_oficio= $datos->id_documentacion_datos_oficio;
        $datos->delete();

        $datos_oficio = \App\Http\Models\DocumentacionDatosOficio::find($id_documentacion_datos_oficio);
        //print_r($datos_oficio);  exit();
        if(count($datos_oficio)>0){ $datos_oficio->delete(); }


        return redirect()->route('recepcion.index')->with('success', "Los datos han sido <b><span style='color:#bd3520'>eliminados</span> satisfactoriamente</b>.");
    }

    public function eliminar_archivo($id_archivo)
    {
        $update=DocumentacionAnexos::find($id_archivo);
        $update->delete();
        \File::delete(storage_path().$update->path.'/'.$update->nombre);
        return redirect()->back();
    }

    public function download($id){
        $anexo= DocumentacionAnexos::find($id);
        $destinationPath= storage_path().'/'.$anexo->path.'/'.$anexo->nombre;

        if(File::exists($destinationPath)){
            return response()->download($destinationPath);
        }else{
            return redirect()->back();
        }
    }

}
