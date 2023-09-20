<?php

namespace App\Http\Controllers;

use DNS2D;
use Milon\Barcode\DNS1D;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class BarcodeController extends Controller
{


    public function generateBarcode(Request $request, $data)
    {
        // $url = url('confir/data/'.$request->barcode);
        // $url = $request->barcode;
        $url = $data;
        $barcodeData =  QrCode::size(400)->generate($url) ;

        // Menampilkan tampilan dengan barcode
        return view('barcode', 
        [
            'barcodeData' => $barcodeData,
            'invoice' => $url
        ]);
    }
}
