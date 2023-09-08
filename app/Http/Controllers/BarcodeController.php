<?php

namespace App\Http\Controllers;

use DNS2D;
use Milon\Barcode\DNS1D;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class BarcodeController extends Controller
{


    public function generateBarcode(Request $request)
    {
        $url = url('confir/data/'.$request->barcode);
        $barcodeData =  QrCode::size(200)->generate($url) ;

        // Menampilkan tampilan dengan barcode
        return view('barcode', 
        [
            'barcodeData' => $barcodeData
        ]);
    }
}
