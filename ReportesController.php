<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Requests\ReporteRequest;
use Carbon\Carbon;
use Response;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Shared\Converter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Excel;
use PHPExcel_IOFactory;
use Auth;
use Illuminate\Support\Str;
use QrCode;
use ZipArchive;

class ReportesController extends Controller
{
    private $route= 'reportes';
    
    public function __construct()
    {
        $this->middleware('auth');
        view()->share('titulo', 'Reportes');
        view()->share('current_route', $this->route);
    }

	public function index() 
    {
		$tipo_reporte = \App\Http\Models\Catalogos\TipoReporte::lists();
		return view('reportes.index',['tipo_reporte'=>$tipo_reporte]);
	}
    
    function download_grafica()
    {

        $destinationPath= storage_path().'/plantillas/grafica.xlsx';

        if(File::exists($destinationPath)){
            return response()->download($destinationPath);
        }
        else{
            return redirect()->back();
        }
    }

    public function generar_reporte(ReporteRequest $request)
    {   
        $de_fecha=$request->input('de_fecha');
        $a_fecha=$request->input('a_fecha');
        $id_tipo_reporte=$request->input('id_tipo_reporte');
    /*
        $objPHPExcel = new \PHPExcel();            
            
            $objWorksheet = $objPHPExcel->getActiveSheet();
      
            $objWorksheet->fromArray(
                array(
                    array('',	        'Totales'),
                    array('Concluidos',   12),
                    array('Proceso',      56),
                    array('Tramite',      52),
                    array('Vencidos',     30),
                )
            );
            
            // Establecer las etiquetas para cada serie de datos que queremos trazar
            //		Tipo de datos
            // Referencia de celda para datos
            // Código de formato
            // Número de puntos de datos en serie
            // Valores de datos
            // Data Marker
            $dataSeriesLabels = array(
            new \PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$B$1', NULL, 1),	//	2010
            );
            // Establecer las etiquetas X-Axis
            //		Tipo de datos
            // Referencia de celda para datos
            // Código de formato
            // Número de puntos de datos en serie
            // Valores de datos
            // Data Marker
            $xAxisTickValues = array(
                new \PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$A$2:$A$5', NULL, 4),	//	Q1 to Q4
            );
            
            // Establecer los valores de datos para cada serie de datos que queremos trazar
            //		Tipo de datos
            // Referencia de celda para datos
            // Código de formato
            // Número de puntos de datos en serie
            // Valores de datos
            // Data Marker
            $dataSeriesValues = array(
                new \PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!$B$2:$B$5', NULL, 4),
            );
            
            // Construir las bases de datos
            $series = new \PHPExcel_Chart_DataSeries(
                \PHPExcel_Chart_DataSeries::TYPE_BARCHART,		// plotType
                \PHPExcel_Chart_DataSeries::GROUPING_STANDARD,	// plotGrouping
                range(0, count($dataSeriesValues)-1),			// plotOrder
                $dataSeriesLabels,								// plotLabel
                $xAxisTickValues,								// plotCategory
                $dataSeriesValues								// plotValues
            );
            
            $series->setPlotDirection(\PHPExcel_Chart_DataSeries::DIRECTION_COLUMN);
            
            $vtrazado_graficos = new \PHPExcel_Chart_PlotArea(NULL, array($series));
            $vleyenda_grafica = new \PHPExcel_Chart_Legend(\PHPExcel_Chart_Legend::POSITION_RIGHT, NULL, false);
            $vtitulo_grafica = new \PHPExcel_Chart_Title('Estadisticas');
            $vtitulo_grafica_y = new \PHPExcel_Chart_Title('Valores');

            $chart = new \PHPExcel_Chart(
                'chart1',		
                $vtitulo_grafica,			
                $vleyenda_grafica,	
                $vtrazado_graficos,		
                true,			
                0,				
                NULL,			
                $vtitulo_grafica_y		
            );
            
            // Establecer la posición donde el gráfico debe aparecer en la hoja de trabajo
            $chart->setTopLeftPosition('A7');
            $chart->setBottomRightPosition('H20');
            
            // Añadir el gráfico a la hoja de trabajo
            $objWorksheet->addChart($chart);
            
            // Save Excel 2007 file
            echo date('H:i:s') , " Write to Excel2007 format" , EOL;
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->setIncludeCharts(TRUE);
            $objWriter->save(storage_path().'/plantillas/grafica.xlsx');
            exit();
        */
        if($id_tipo_reporte==1){ 
            
            setlocale(LC_ALL,'es_ES');           
            $PHPWord= new PhpWord();
            $templateWord= $PHPWord->loadTemplate(storage_path().'/plantillas/rptEjecutivo.docx');         
                                
            $d_fec=\App\Http\Classes\clsFormatDates::shortDateFormatTime($de_fecha,0);
            $templateWord->setValue('de_fecha', \App\Http\Classes\clsFormatDates::solo_dia_mes($d_fec,1));
            
            $a_fec=\App\Http\Classes\clsFormatDates::shortDateFormatTime($a_fecha,0);
            $templateWord->setValue('a_fecha', \App\Http\Classes\clsFormatDates::longDateFormat_day($a_fec,1));
            
            $templateWord->setValue('de_fecha_1', \App\Http\Classes\clsFormatDates::solo_dia_mes($d_fec,1));
            $templateWord->setValue('a_fecha_1', \App\Http\Classes\clsFormatDates::longDateFormat_day($a_fec,1));
            $templateWord->setValue('de_fecha_2', \App\Http\Classes\clsFormatDates::solo_dia_mes($d_fec,1));
            $templateWord->setValue('a_fecha_2', \App\Http\Classes\clsFormatDates::longDateFormat_day($a_fec,1));
            $templateWord->setValue('de_fecha_3', \App\Http\Classes\clsFormatDates::solo_dia_mes($d_fec,1));
            $templateWord->setValue('a_fecha_3', \App\Http\Classes\clsFormatDates::longDateFormat_day($a_fec,1));
            
            $header_tbl= \App\Http\Classes\clsFormatDates::solo_dia_mes($d_fec,1).' al '.\App\Http\Classes\clsFormatDates::longDateFormat_day($a_fec,1);
            $templateWord->setValue('encabezado_tbl', $header_tbl);
            
            $rango_de=$d_fec.' 00:00:00';
            $rango_a=$a_fec.' 23:59:59';            
            
            $vtotal_pendientes=count(\App\Http\Models\DocumentacionHistorial::total_pendientes($rango_de, $rango_a, 1)->get());//PENDIENTES ->
            $vtotal_turnados=count(\App\Http\Models\DocumentacionHistorial::total_turnados($rango_de, $rango_a)->get());//TURNADOS ->
            $vtotal_documentos=count(\App\Http\Models\DocumentacionHistorial::total_ingresados($rango_de, $rango_a)->get());//RECIBIDAS ->
            $vtotal_concluidos=count(\App\Http\Models\DocumentacionHistorial::total_concluidos($rango_de, $rango_a, 3)->get());//CONCLUIDOS ->
            $vtotal_proceso=count(\App\Http\Models\DocumentacionHistorial::total_concluidos($rango_de, $rango_a, 2)->get());//PROCESO
            

            
            $objPHPExcel=new \PHPExcel();
            $objWorksheet=$objPHPExcel->getActiveSheet();
            $objWorksheet->fromArray(
                array(
                    array('',	        'Totales'),
                    array('Turnados',   $vtotal_turnados),
                    array('Concluidos',   $vtotal_concluidos),
                    array('Proceso',      $vtotal_proceso),
                    array('Pendientes',     $vtotal_pendientes),
                )
            );            

            $dataSeriesLabels = array( new \PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$B$1', NULL, 1), );
            $xAxisTickValues = array( new \PHPExcel_Chart_DataSeriesValues('String', 'Worksheet!$A$2:$A$5', NULL, 4),	);
            $dataSeriesValues = array( new \PHPExcel_Chart_DataSeriesValues('Number', 'Worksheet!$B$2:$B$5', NULL, 4), );

            $series = new \PHPExcel_Chart_DataSeries(
                \PHPExcel_Chart_DataSeries::TYPE_BARCHART,		// plotType
                \PHPExcel_Chart_DataSeries::GROUPING_STANDARD,	// plotGrouping
                range(0, count($dataSeriesValues)-1),			// plotOrder
                $dataSeriesLabels,								// plotLabel
                $xAxisTickValues,								// plotCategory
                $dataSeriesValues								// plotValues
            );
            
            $series->setPlotDirection(\PHPExcel_Chart_DataSeries::DIRECTION_COLUMN);
            
            $plotArea = new \PHPExcel_Chart_PlotArea(NULL, array($series));
            $legend = new \PHPExcel_Chart_Legend(\PHPExcel_Chart_Legend::POSITION_RIGHT, NULL, false);
            $title = new \PHPExcel_Chart_Title('Estadisticas');
            $yAxisLabel = new \PHPExcel_Chart_Title('Valores');

            $chart = new \PHPExcel_Chart(
                'chart1',		
                $title,			
                $legend,		
                $plotArea,		
                true,			
                0,				
                NULL,		
                $yAxisLabel	
            );

            $chart->setTopLeftPosition('A7');
            $chart->setBottomRightPosition('H20');

            $objWorksheet->addChart($chart);

            echo date('H:i:s') , " Write to Excel2007 format" , EOL;
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->setIncludeCharts(TRUE);
            $objWriter->save(storage_path().'/reporte/grafica.xlsx');
            
            
            
            
            
            
            
            //$vtotal_pendiente=$vtotal_pendientes - $vtotal_turnados;
            $templateWord->setValue('total_pendiente', $vtotal_pendientes);
            $templateWord->setValue('total_general', $vtotal_documentos);
            $templateWord->setValue('total_concluidos', $vtotal_concluidos);
            $templateWord->setValue('total_c', $vtotal_concluidos);
            $templateWord->setValue('total_concluidos', $vtotal_concluidos);
            $templateWord->setValue('total_c', $vtotal_concluidos);
            $templateWord->setValue('total_tur', $vtotal_turnados);
            $templateWord->setValue('total_turnados', $vtotal_turnados);
            /* $vtotal_proceso = $total_proceso - $vtotal_concluidos */
            $templateWord->setValue('total_proceso', $vtotal_proceso);
                      
            $porcentaje_gral = round(($vtotal_concluidos * 100) / $vtotal_documentos);
            $templateWord->setValue('porcentaje_total', $porcentaje_gral);

            $consultas= \App\Http\Models\Catalogos\Area::listar_direcciones()->get();
            $templateWord->cloneRow('celda1', count($consultas));
            $i=1;
            $vtotal_proceso=0;
            $vtotal_concluido=0;
            $vtotal_vencido=0;
            foreach($consultas as $consulta){
                $date_actual = Carbon::now();
                $date_actual = $date_actual->format('Y-m-d').' 23:59:59';                
                $turnados_proceso= count(\App\Http\Models\DocumentacionHistorial::total_documetacion_areas($rango_de, $rango_a, 2, $consulta->id)->get());
                $turnados_concluidos= count(\App\Http\Models\DocumentacionHistorial::total_documetacion_areas($rango_de, $rango_a, 3, $consulta->id)->get());
                $documentos_vencidos= count(\App\Http\Models\DocumentacionHistorial::total_documetacion_areas_vencidas($rango_de, $rango_a, 2, $consulta->id)->get());
                          
                $templateWord->setValue('celda1#'.$i, $i);
                $templateWord->setValue('celda2#'.$i, $consulta->direccion);
                $templateWord->setValue('celda3#'.$i, $turnados_proceso);             
                $templateWord->setValue('celda4#'.$i, $turnados_concluidos);
                $templateWord->setValue('celda5#'.$i, $documentos_vencidos);

                $vtotal_proceso=$vtotal_proceso + $turnados_proceso;
                $vtotal_concluido=$vtotal_concluido + $turnados_concluidos;
                $vtotal_vencido=$vtotal_vencido + $documentos_vencidos;
                $i++;
            }
            $templateWord->setValue('total_proceso', $vtotal_proceso);
            $templateWord->setValue('total_concluido', $vtotal_concluido);
            $templateWord->setValue('total_vencido', $vtotal_vencido);
            
            $templateWord->saveAs(storage_path().'/reporte/reporte_ejecutivo.docx');
            //$templateWord->saveAs('plantillas/rptEjecutivo.docx');           
            //return redirect()->back()->with('success', "Los datos han sido actualizados <b>satisfactoriamente</b>.");
            
            return redirect()->route('zip.index');

            //return redirect()->route('reportes.index');
        }
        if($id_tipo_reporte==2){           
            $vresponse = \App\Http\Models\ejecutiva\RecibidaCoordinacion::recibida([
                'id_area'=>Auth::User()->id_area,
                'id_tipo_turnado' => 1
            ])->get();
            
    		$mpdf= new \mPDF('utf-8', 'Letter', '','',20, 20, 30, 30);
    		$mpdf->mirrorMargins = 1;
    		$mpdf->showImageErrors=true;
    
    		$mpdf->SetTitle('Reporte Oficio');
    		$mpdf->SetAuthor('Autor UDI');
    		$mpdf->SetDisplayMode(75);
    
    		$vimagenIzquierda=public_path()."/img/gobierno_del_estado_de_chiapas.png";
    		$vimagenDerecha=public_path()."/img/scg.png";
    		$vimagenPieDerecha=public_path()."/img/chiapas_nos_une.png";
    
    		$vheader.='
    		<table width="100%">
    			<tr>
    				<td width="25%" style="text-align: left">
    					<img src='.$vimagenIzquierda.' width="150px" />
    				</td>
    				<td width="50%" style="text-align: center; font-size: 12px">
    					<p>
    						<strong>
    							GOBIERNO DEL ESTADO DE CHIAPAS<br>
    							Secretaría de la Contraloría General
    						</strong><br>
    					</p>
    				</td>
    				<td width="25%" style="text-align: right">
    					<img src='.$vimagenDerecha.' width="148px" />
    				</td>
    			<tr>
    		</table>';
    
    		$vfooter ='	<div>';   				
    		$vfooter.='		<table  width="100%" style="font-size: 8px">';
    		$vfooter.='			<tr>';
    		$vfooter.='				<td width="70%" style="text-align: left">';
    		$vfooter.=					$datos->direccion . '<br />';
    		$vfooter.='					Conmutador '. $datos->conmutador;
    		$vfooter.='				</td>';
    		$vfooter.='				<td width="30%" style="text-align: right">';
    		$vfooter.='					<img src='.$vimagenPieDerecha.' width="110px" />';
    		$vfooter.='				</td>';
    		$vfooter.='			</tr>';
    		$vfooter.='		</table>';		
    		$vfooter.='	</div>';   		
            
            $strhtml='
            <html>
                <head></head>
                <body>
                    <table style="width: 100%; border: #eee 1px solid;">
                        <tr bgcolor="#eee">
                            <td style="width: 10%; font-size:80%; border: #eee 1px solid;">Folio</td>
                            <td style="width: 15%; font-size:80%; border: #eee 1px solid;">Remitente</td>
                            <td style="width: 15%; font-size:80%; border: #eee 1px solid;">Direccion</td>
                            <td style="width: 60%; font-size:80%; border: #eee 1px solid;">Asunto</td>
                        </tr>';
            foreach ($vresponse as $vrespuesta){
            $direccion=\App\Http\Models\Catalogos\Area::find($vrespuesta->id_area_turnado);      
            $strhtml.=' <tr>';
            $strhtml.='     <td style="width: 10%; font-size:60%; border: #eee 1px solid; text-align: center;">'. $vrespuesta->num_oficio .'</td>';
            $strhtml.='     <td style="width: 15%; font-size:60%; border: #eee 1px solid; text-align: center;">'. $vrespuesta->dependencia_oficio .'</td>';
            $strhtml.='     <td style="width: 15%; font-size:60%; border: #eee 1px solid; text-align: center;">'. $direccion->area.'</td>';
            $strhtml.='     <td style="width: 60%; font-size:60%; border: #eee 1px solid; text-align: justify;">'. strip_tags($vrespuesta->cuerpo) .'</td>';
            $strhtml.=' </tr>';                
            }
            $strhtml.='
                    </table>                
                </body>
            </html>';   
            
            $mpdf->SetHTMLHeader($vheader);    		
    		$mpdf->SetHTMLFooter($vfooter);
    		$mpdf->WriteHTML($strhtml);
    		$mpdf->Output();
    		exit();
        }
       
    }

    //oficio enviado
	public function rpt_oficio($id_oficio)
	{
		//obtengo los datos
		$datos = \App\Http\Models\DocumentacionEnviadaCoordinacion::reporteOficioInterno($id_oficio)->first();
		//cambiar los valores primer valor es el margen izquiedo, segundo valor es el margen derecho,
		//tercer valor es el margen superior y el cuarto valor es el margen inferior
		$mpdf = new \mPDF('utf-8', 'Letter', '','',20, 20, 35, 35);
		$mpdf->mirrorMargins = 1;
		$mpdf->showImageErrors=true;

		//codigo para agregar marca de agua, y autor al pdf
		$mpdf->SetTitle('Reporte Oficio');
		$mpdf->SetAuthor('Autor UDI');
		//Set the display magnification
		$mpdf->SetDisplayMode(75);
		//Apply a Watermark
		// $mpdf->SetWatermarkText('Etapa de prueba');
		$mpdf->showWatermarkText = true;
		//Finaliza marca de agua

		$vimagenIzquierda=public_path()."/img/gobierno_del_estado_de_chiapas.png";
		$vimagenDerecha=public_path()."/img/scg.png";
		$vimagenPieDerecha=public_path()."/img/chiapas_nos_une.png";

		$header.='
		<table width="100%">
			<tr>
				<td width="25%" style="text-align: left">
					<img src='.$vimagenIzquierda.' width="150px" />
				</td>
				<td width="50%" style="text-align: center; font-size: 12px">
					<p>
                        <strong>';
		$area=\App\Http\Models\Catalogos\Area::find($datos->id_area_envia);
		$header.='        '. $area->area .'	';				
		$header.='      </strong><br>
					</p>
				</td>
				<td width="25%" style="text-align: right">
					<img src='.$vimagenDerecha.' width="148px" />
				</td>
			<tr>
		</table>';

		$headerE='
		<table width="100%">
			<tr>
				<td width="10%">
					<img src='.$vimagenIzquierda.' width="100px" />
				</td>
				<td width="80%" style="text-align:center">
					<p><strong>GOBIERNO DEL ESTADO DE CHIAPAS<br>Secretaría de la Contraloría General</strong><br>Oficina del C. Secretario</p>
				</td>
				<td width="10%">
					<img src='.$vimagenDerecha.' width="130px" />
				</td>
			<tr>
		</table>';	

		$png = QrCode::format('png')->size(140)->color(40, 34, 98)->generate('http://pki.firmaelectronica.chiapas.gob.mx/siged/reportes/oficio/'. $id_oficio);//"Oficio no. ". $datos->folio);
		$png = base64_encode($png);
    
        $vdependencia_origen= \App\Http\Models\Catalogos\Dependencia::find($area->id_dependencia);
        
		$footer ='	<div>';
		$footer.='		<table  width="100%" style="font-size: 8px">';
		$footer.='			<tr>';
		$footer.='				<td width="70%" style="text-align: left">';
		$footer.=					$vdependencia_origen->direccion . '<br />';
		$footer.='					Conmutador '. $vdependencia_origen->conmutador;
		$footer.='				</td>';
		$footer.='				<td width="30%" style="text-align: right">';
		$footer.='					<img src='.$vimagenPieDerecha.' width="110px" />';
		$footer.='				</td>';
		$footer.='			</tr>';
		$footer.='		</table>';		
		$footer.='	</div>';
        /*
		$footer ='	<div>';
		$footer.='		<table  width="100%" style="font-size: 8px">';
		$footer.='			<tr>';
		$footer.='				<td width="70%" style="text-align: left">';
		$footer.=					$datos->direccion . '<br />';
		$footer.='					Conmutador '. $datos->conmutador;
		$footer.='				</td>';
		$footer.='				<td width="30%" style="text-align: right">';
		$footer.='					<img src='.$vimagenPieDerecha.' width="110px" />';
		$footer.='				</td>';
		$footer.='			</tr>';
		$footer.='		</table>';		
		$footer.='	</div>';
        */

		//Saber para que es esto
		$footerE ='	<div align="right">';
		$footerE.='		<table width="100%" style=" font-size:8px">';
		$footerE.='			<tr>';
		$footerE.='				<td rowspan="2">';
		$footerE.="				   <img src='data:image/png;base64," . $png . "'>";
		$footerE.='				</td>';
		$footerE.='				<td colspan="3">';
		$footerE.='					<table style="font-size:10px">';
		$footerE.='						<tr>';
		$footerE.='							<td valign="top">';
		$footerE.='								<strong>C.c.p.-</strong>';
		$footerE.='							</td>';
		$footerE.='							<td>';
		$copias=json_decode($datos->cc, true);    
        if(count($copias)>0) {
			 foreach ($copias as $copia){
				$area_copia= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($copia);
				$footerE.='						<strong>'.$area_copia->titulo.' '. $area_copia->nombre.' '.$area_copia->ap_paterno.' '.$area_copia->ap_materno.'.</strong> '.$area_copia->area.'<br>';
			}
		}               
		$footerE.='								Archivo/Minutario';
		$footerE.='							</td>';
		$footerE.='						</tr>';
		$footerE.='					</table>';
		$footerE.='				</td>';
		$footerE.='			</tr>';
		$footerE.='			<tr>';
		$footerE.='				<td colspan="3">';
		if($datos->datos_firma<>NULL){
			$footerE.='				<p style="font-size:10px;"> <strong>Firma electronica:</strong> '.$datos->datos_firma.'</p>';
		}
		$footerE.='				</td>';
		$footerE.='			</tr>';
		$footerE.='			<tr>';
		$footerE.='				<td></td>';
		$footerE.='				<td>';
		$footerE.='					Fecha de impresion: '.DATE('d/m/Y');
		$footerE.='				</td>';
		$footerE.='				<td>';
		$footerE.='					<img src='.$vimagenIzquierda.' width="80px" />';
		$footerE.='				</td>';
		$footerE.='			</tr>';
		$footerE.='		</table>';
		$footerE.='	</div>';
		//termina footer

		$mpdf->SetHTMLHeader($header);
        $mpdf->SetHTMLFooter($footer);
        $mpdf->SetHTMLHeader($header,'E');
        $mpdf->SetHTMLFooter($footer, 'E');
		//$mpdf->SetHTMLHeader($headerE,'E');
		//$mpdf->SetHTMLFooter($footerE,'E');
        
        $strhtml='';
		$strhtml.='	<html>';
		$strhtml.='		<head></head>';
		$strhtml.='		<body>';
		$strhtml.='			<br>';
		$strhtml.='			<table width="100%">';
		$strhtml.='				<tr>';
		$strhtml.='					<td width="35%" style="vertical-align:top; font-size:12px; text-align:right">';
		$strhtml.=$datos->tipo_documento.' No. <b>'.$datos->folio.'</b><br>Tuxtla Gutiérrez, Chiapas<br>'.\App\Http\Classes\clsFormatDates::longDateFormat_day($datos->fecha_envio, 1);//$datos->fecha_creacion,1);
		$strhtml.='					</td>';
		$strhtml.='				</tr>';
		$strhtml.='				<tr>';
		$strhtml.='					<td width="55%" style="vertical-align:top; font-size:12px; text-align:left;"><br><br>';
		$destinatarios= json_decode($datos->destinatarios, true);
		if(count($destinatarios) > 0){
			$l_destinatarios=""; $l_areas=""; $separador=''; $l_dependencia=""; $last_dependencia='';
			foreach ($destinatarios as $destinatario){
				$area_dest= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($destinatario);
				$strhtml.='				<strong>'.$area_dest->titulo.' '.$area_dest->nombre.' '.$area_dest->ap_paterno.' '.$area_dest->ap_materno.'</strong><br>'.$area_dest->area.'<br>';
			}
		}
        else {
            //Componer por aqui
            $documentacion=\App\Http\Models\DocumentacionEnviadaCoordinacion::find($id_oficio);
            $documentacionOficio=\App\Http\Models\DocumentacionDatosOficio::find($documentacion->id_documentacion);
            $vdependencia=\App\Http\Models\Catalogos\Dependencia::find($documentacionOficio->id_dependencia_oficio);
            
            $strhtml.='				     <strong>'. $documentacionOficio->remitente_oficio . '</strong><br />'.$documentacionOficio->remitente_cargo_oficio.'<br />'. $vdependencia->nombre; 
        }
		$strhtml.='					</td>';
		$strhtml.='				</tr>';
		$strhtml.='			</table>';
		$strhtml.='			<p style="font-size:12px; text-align:justify;">'.$datos->cuerpo.'</p>';    
		$strhtml.='			<p style="font-size:12px;"><strong>Atentamente</strong></p>';
        $strhtml.='         <br /><br /><br />';
		$area_envia= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($datos->id_area_envia);
		$strhtml.='			<p style="font-size:12px;"><strong>'.$area_envia->titulo.' '.$area_envia->nombre.' '.$area_envia->ap_paterno.' '.$area_envia->ap_materno.'</strong><br>'.$area_envia->area.'<br></p>';
		
        $strhtml.='           <table style="font-size: 8px">';
		$strhtml.='               <tr>';
		$strhtml.='                   <td valign="top">';
		$strhtml.='                       <strong>C.c.p.-</strong>';
		$strhtml.='                   </td>';
		$strhtml.='                   <td>';
		$copias=json_decode($datos->cc, true);
		if(count($copias)>0){
			foreach ($copias as $copia){
				$area_copia= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($copia);
				$strhtml.='               <strong>'.$area_copia->titulo.' '. $area_copia->nombre.' '.$area_copia->ap_paterno.' '.$area_copia->ap_materno.'.</strong> '.$area_copia->area.'<br>';
			}
		}
		$strhtml.='					      Archivo/Minutario';
		$strhtml.='				       </td>';
		$strhtml.='               </tr>';
		$strhtml.='           </table>';        
        if($datos->id_tipo_documento != 1){
            if($datos->datos_firma){
                $strhtml.='  <table style="width: 100%;">';
                $strhtml.='      <tr>';
                $strhtml.='          <td style="width: 20%; text-align: center;">';
                $strhtml.='              <img src="data:image/png;base64,'.$png.'">'; 
                $strhtml.='          </td>';
                
                $strhtml.='          <td style="font-size: 9px; width: 80%; font-family: "Arial", serif; text-align: justify;">';
                $strhtml.='				 ||'.$datos->serie_firma.'|'.$datos->secuencia_firma.'|'.$datos->fecha_firma.'||</p> <br />';
                $strhtml.='                  '.substr($datos->datos_firma, 0, 72).'<br />' .substr($datos->datos_firma, 72, 72).'<br />' .substr($datos->datos_firma, 144, 72);
                $strhtml.='				 <br /><br /><p>Este documento ha sido Firmado Electrónicamente, Teniendo el mismo valor que la firma autógrafa de acuerdo a los Artículos 1, 3, 8 y 11 de la Ley de Firma Electrónica Avanzada del Estado de Chiapas.</p>';
                $strhtml.='              <br /> URL de Validacion de firma Electrónica.<br /> http://pki.firmaelectronica.chiapas.gob.mx/verificacion/index.php?vsecuencia='.$datos->secuencia_firma;               
                $strhtml.='          </td>';
                
                $strhtml.='      </tr>';
                $strhtml.='  </table>';                             
            }			
		}
        $strhtml.='		</body>';
		$strhtml.='	</html>';
		$mpdf->WriteHTML($strhtml);
		$mpdf->Output();
		exit();
	}

  //oficio de la coordinacion ejecutiva
    public function rpt_CE($id_oficio_turnado)
    {
        //obtengo los datos
        $datos = \App\Http\Models\DocumentacionEnviadaCoordinacion::reporteCE($id_oficio_turnado)->first();
        //cambiar los valores primer valor es el margen izquiedo, segundo valor es el margen derecho,
        //tercer valor es el margen superior y el cuarto valor es el margen inferior
        $mpdf = new \mPDF('utf-8', 'Letter', '','',10,10,25, 20);
        $mpdf->mirrorMargins = 1;
        $mpdf->showImageErrors=true;
        
        //codigo para agregar marca de agua, y autor al pdf
        $mpdf->SetTitle('Reporte Oficio');
        $mpdf->SetAuthor('Autor UDI');
        //Set the display magnification       
        
        $nosune = public_path()."/img/nosune.png";
        $sfp = public_path()."/img/scg.png";
        
        $header .= '
        <table width="100%">
        <tr>
        <td width="10%">
        <img src='.$nosune.' width="100px" />
        </td>
        <td width="80%" style="text-align:center">
        <p><strong>GOBIERNO DEL ESTADO DE CHIAPAS<br>Secretaría de la Contraloría General</strong><br>Oficina del C. Secretario</p>
        </td>
        <td width="10%">
        <img src='.$sfp.' width="130px" />
        </td>
        <tr>
        </table>
        ';
        
        $headerE =   '
        <table width="100%">
        <tr>
        <td width="10%">
        <img src='.$nosune.' width="100px" />
        </td>
        <td width="80%" style="text-align:center">
        <p><strong>GOBIERNO DEL ESTADO DE CHIAPAS<br>Secretaría de la Contraloría General</strong><br>Oficina del C. Secretario</p>
        </td>
        <td width="10%">
        <img src='.$sfp.' width="130px" />
        </td>
        <tr>
        </table>
        ';

        //inicia footer
        $footer = '<div align="right">';
        $footer.='<table width="100%" style=" font-size:8px">';
        $footer.='<tr>';
        $footer.='<td colspan="3">';
        $footer.='<table style="font-size:10px">';
        $footer.='<tr>';
        $footer.='<td valign="top">';
        $footer.='<strong>C.c.p.-</strong>';
        $footer.='</td>';
        $footer.='<td>';
        $copias= json_decode($datos->cc, true);
        if(count($copias)>0)
        {
        foreach ($copias as $copia)
        {
        $area_copia= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($copia);
        
        $footer.='<strong>'.$area_copia->titulo.' '. $area_copia->nombre.' '.$area_copia->ap_paterno.' '.$area_copia->ap_materno.'.</strong> '.$area_copia->area.'<br>';
        }
        }
        $footer.='Archivo/Minutario';
        $footer.='</td>';
        $footer.='</tr>';
        $footer.='</table>';
        $footer.='</td>';
        $footer.='</tr>';
        
        $footer.='<tr>';
        $footer.='<td colspan="3">';
        if($datos->datos_firma<>NULL)
        {
        $footer.='<p style="font-size:10px;"> <strong>Firma electronica:</strong> '.$datos->datos_firma.'</p>';
        }
        $footer.='</td>';
        $footer.='</tr>';
        
        $footer.='<tr>';
        $footer.='<td>';
        $footer.='<img src='.$nosune.' width="80px" />';
        $footer.='</td>';
        $footer.='<td>';
        $footer.= 'Fecha de impresion: '.DATE('d/m/Y');
        $footer.='</td>';
        $footer.='<td>';
        $footer.='{PAGENO}';
        $footer.='</td>';
        $footer.='</tr>';
        $footer.='</table>';
        $footer.='</div>';
        
        $footerE = '<div align="right">';
        $footerE.='<table width="100%" style=" font-size:8px">';
        $footerE.='<tr>';
        $footerE.='<td colspan="3">';
        $footerE.='<table style="font-size:10px">';
        $footerE.='<tr>';
        $footerE.='<td valign="top">';
        $footerE.='<strong>ccp.-</strong>';
        $footerE.='</td>';
        $footerE.='<td>';
        $copias= json_decode($datos->cc, true);
        if(count($copias)>0)
        {
        foreach ($copias as $copia)
        {
        $area_copia= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($copia);
        
        $footerE.='<strong>'.$area_copia->titulo.' '. $area_copia->nombre.' '.$area_copia->ap_paterno.' '.$area_copia->ap_materno.'.</strong> '.$area_copia->area.'<br>';
        }
        }
        $footerE.='Archivo/Minutario';
        $footerE.='</td>';
        $footerE.='</tr>';
        $footerE.='</table>';
        $footerE.='</td>';
        $footerE.='</tr>';
        
        $footerE.='<tr>';
        $footerE.='<td colspan="3">';
        if($datos->datos_firma<>NULL)
        {
        $footerE.='<p style="font-size:10px;"> <strong>Firma electronica:</strong> '.$datos->datos_firma.'</p>';
        }
        $footerE.='</td>';
        $footerE.='</tr>';
        
        
        $footerE.='<tr>';
        $footerE.='<td>';
        $footerE.='<img src='.$nosune.' width="80px" />';
        $footerE.='</td>';
        $footerE.='<td>';
        $footerE.= 'Fecha de impresion: '.DATE('d/m/Y');
        $footerE.='</td>';
        $footerE.='<td>';
        $footerE.='{PAGENO}';
        $footerE.='</td>';
        $footerE.='</tr>';
        $footerE.='</table>';
        $footerE.='</div>';
        //termina footer
        
        $mpdf->SetHTMLHeader($header);
        $mpdf->SetHTMLHeader($headerE,'E');
        $mpdf->SetHTMLFooter($footer);
        $mpdf->SetHTMLFooter($footerE,'E');
        
        $strhtml='';
        $strhtml.='<html>';
        $strhtml.='<head>';
        $strhtml.='</head>';
        $strhtml.='<body>';
        $strhtml.='<br><br>';
        $strhtml.='<table width="100%">';
        $strhtml.='<tr>';
        $strhtml.='<td width="10%" style="vertical-align:top; font-size:12px">';
        $strhtml.='<strong>PARA:</strong> ';
        $strhtml.='</td>';
        $strhtml.='<td width="55%" style="vertical-align:top; font-size:12px; text-align:left;">';
        
        $destinatarios= json_decode($datos->destinatarios, true);
        if(count($destinatarios)>0)
        {
        $l_destinatarios=""; $l_areas=""; $separador=''; $l_dependencia=""; $last_dependencia='';
        foreach ($destinatarios as $destinatario)
        {
        $area_dest= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($destinatario);
        
        $strhtml.='<strong>'.$area_dest->titulo.' '.$area_dest->nombre.' '.$area_dest->ap_paterno.' '.$area_dest->ap_materno.'</strong><br>'.$area_dest->area.'<br>';
        }
        }
                
        $strhtml.='</td>';
        $strhtml.='<td width="35%" style="vertical-align:top; font-size:12px; text-align:right">';
        $strhtml.='Tuxtla Gutiérrez, Chiapas<br>';
        $strhtml.='Num. <b>'.$datos->folio.'</b>';
        $strhtml.='</td>';
        $strhtml.='</tr>';
        $strhtml.='</table>';
        
        $strhtml.='<br><p style="font-size:12px; text-align:justify;">La oficina del C. Secretario le hace llegar el documento y los anexos en su caso, lo que al calce se detalla, para la atenci&oacute;n procedente y con la atenta s&uacute;plica de que informe sobre los resultados de su intervenci&oacute;n al respecto para el seguimiento en el Sistema de Control de Gesti&oacute;n.</p>';
        
        $strhtml.='<p style="font-size:12px; text-align:justify;">Lo anterior, con fundamento en los dispuesto en los articulos 16 primer p&aacute;rrafo de la Constituci&oacute;n Pol&iacute;tica de los Estados Unidos Mexicanos, 30 de la Ley Org&aacute;nica de la Administraci&oacute;n P&uacute;blica del Estado de Chiapas; 11 y 12, fracci&oacute;n VII del Reglamento Interno de la Secretar&iacute;a de la Contralor&iacute;a General del Estado de Chiapas.</p>';
        
        $strhtml.='<table width="100%">';
        $strhtml.='<tr>';
        $strhtml.='<td width="50%" style="vertical-align:top;">';
        
        $strhtml.='<table width="100%" cellspacing="14">';
        $strhtml.='<tr>';
        $strhtml.='<td width="37%" style="font-size:12px">';
        $strhtml.='<strong>Fecha del Oficio:</strong>';
        $strhtml.='</td>';
        
        $strhtml.='<td width="63%" style="font-size:12px">';
        $strhtml.=\App\Http\Classes\clsFormatDates::shortDateFormat($datos->fecha_oficio,1);
        $strhtml.='</td>';
        $strhtml.='</tr>';
        
        $strhtml.='<tr>';
        $strhtml.='<td width="37%" style="font-size:12px">';
        $strhtml.='<strong>Fecha Recepcion:</strong>';
        $strhtml.='</td>';
        
        $strhtml.='<td width="63%" style="font-size:12px">';
        $strhtml.=\App\Http\Classes\clsFormatDates::shortDateFormat($datos->fecha_recepcion_oficio,1);
        $strhtml.='</td>';
        $strhtml.='</tr>';
        
        $strhtml.='<tr>';
        $strhtml.='<td width="37%" style="font-size:12px">';
        $strhtml.='<strong>Fecha de Turno:</strong>';
        $strhtml.='</td>';
        
        $strhtml.='<td width="63%" style="font-size:12px">';
        $strhtml.=\App\Http\Classes\clsFormatDates::shortDateFormat($datos->sended_at,1);
        $strhtml.='</td>';
        $strhtml.='</tr>';
        
        $strhtml.='<tr>';
        $strhtml.='<td width="37%" style="font-size:12px">';
        $strhtml.='<strong>No. Oficio:</strong>';
        $strhtml.='</td>';
        
        $strhtml.='<td width="63%" style="font-size:12px">';
        $strhtml.=$datos->num_oficio;
        $strhtml.='</td>';
        $strhtml.='</tr>';
        
        $strhtml.='<tr>';
        $strhtml.='<td width="37%" style="font-size:12px">';
        $strhtml.='<strong>Enlace:</strong>';
        $strhtml.='</td>';
        
        $strhtml.='<td width="63%" style="font-size:12px">';
        $strhtml.=$datos->remitente_oficio;
        $strhtml.='</td>';
        $strhtml.='</tr>';
        
        $strhtml.='<tr>';
        $strhtml.='<td width="37%" style="font-size:12px">';
        $strhtml.='<strong>Destinatario:</strong>';
        $strhtml.='</td>';
        
        $strhtml.='<td width="63%" style="font-size:12px">';
        
        $destinatarios2= json_decode($datos->destinatarios_oficio, true);
        if(count($destinatarios2)>0)
        {
        foreach ($destinatarios2 as $dest)
        {
        $a_dest= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($dest);
        
        $strhtml.='<strong>'.$a_dest->titulo.' '.$a_dest->nombre.' '.$a_dest->ap_paterno.' '.$a_dest->ap_materno.'</strong><br>'.$a_dest->area.'<br>';
        }
        }
        
        $strhtml.='</td>';
        $strhtml.='</tr>';
        
        $strhtml.='<tr>';
        $strhtml.='<td width="37%" style="font-size:12px">';
        $strhtml.='<strong>Tipo asignación:</strong>';
        $strhtml.='</td>';
        
        $strhtml.='<td width="63%" style="font-size:12px">';
        $strhtml.=$datos->tipo_asignacion;
        $strhtml.='</td>';
        $strhtml.='</tr>';
        
        $strhtml.='<tr>';
        $strhtml.='<td style="font-size:12px" colspan="2">';
        $strhtml.='<strong>Fecha de informe de resultados:</strong> '.\App\Http\Classes\clsFormatDates::shortDateFormat($datos->fecha_vencimiento,1);
        $strhtml.='</td>';
        $strhtml.='</tr>';
        $strhtml.='</table>';
        
        $strhtml.='</td>';
        
        $strhtml.='<td width="50%" style="vertical-align:top;">';
        $strhtml.=' <table width="100%" cellspacing="14">';
        $strhtml.='     <tr>';
        $strhtml.='         <td width="33%" style="font-size:12px; vertical-align:top;">';
        $strhtml.='             <strong>Asunto:</strong>';
        $strhtml.='         </td>';        
        $strhtml.='         <td width="67%" style="font-size:12px; text-align:justify;">';
        $strhtml.=              $datos->asunto_turnado;
        $strhtml.='         </td>';
        $strhtml.='     </tr>';  
        $strhtml.='     <tr>';
        $strhtml.='         <td width="33%" style="font-size:12px; vertical-align:top;">';
        $strhtml.='             <strong>Observacion:</strong>';
        $strhtml.='         </td>';        
        $strhtml.='         <td width="67%" style="font-size:12px; text-align:justify;">';
        $strhtml.=              $datos->observacion_turnado;
        $strhtml.='         </td>';
        $strhtml.='     </tr>';       
        $strhtml.=' </table>';
        $strhtml.='</td>';
        $strhtml.='</tr>';
        $strhtml.='</table>';
        
        $strhtml.='<p style="font-size:12px; text-align:justify;">Sin m&aacute;s particular agradecemos su atenci&oacute;n al tiempo de suscribirnos a sus apreciables &oacute;rdenes.</p>';
        $strhtml.='<p style="font-size:12px;"><strong>Atentamente</strong></p>';
        $area_envia= \App\Http\Models\Catalogos\Area::buscar_persona_oficio($datos->id_area_envia);
        
        $strhtml.='<p style="font-size:12px;"><strong>'.$area_envia->titulo.' '.$area_envia->nombre.' '.$area_envia->ap_paterno.' '.$area_envia->ap_materno.'</strong><br>'.$area_envia->area.'<br></p>';
        
        $strhtml.='</body>';
        $strhtml.='</html>';
        
        $mpdf->WriteHTML($strhtml);
        $mpdf->Output();
        exit();
    }

  //oficio enviado
    public function rpt_ejecutivo()
    {
        return response()->download('plantillas/rptEjecutivo.docx');
    }
}