<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use ZipArchive;

class ZipController extends Controller
{
    public function zipFileDownload(){
    
        $files = glob(storage_path("reporte/*.*"));
        $archiveFile = storage_path("reporte/reporte_ejecutivo.zip");
        $archive = new ZipArchive();

        // check if the archive could be created.
        if ($archive->open($archiveFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            // loop trough all the files and add them to the archive.
            foreach ($files as $file) {
                if ($archive->addFile($file, basename($file))) {
                    // do something here if addFile succeeded, otherwise this statement is unnecessary and can be ignored.
                    continue;
                } else {
                    throw new Exception("file `{$file}` could not be added to the zip file: " . $archive->getStatusString());
                }
            }

            // close the archive.
            if ($archive->close()) {
                // archive is now downloadable ...
                return response()->download($archiveFile, basename($archiveFile))->deleteFileAfterSend(true);
            } else {
                throw new Exception("could not close zip file: " . $archive->getStatusString());
            }
        } else {
            throw new Exception("zip file could not be created: " . $archive->getStatusString());
        }
        
    }
}
