<?php

namespace App\Http\Controllers;

use DNS2D;
use Milon\Barcode\DNS1D;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Event;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class BarcodeController extends Controller
{


    public function generateBarcode(Request $request, $data)
    {
        // $url = url('confir/data/'.$request->barcode);
        // $url = $request->barcode;
        $url = $data;
        $cart = Cart::where('invoice', $url)->first();
        $event = Event::where('uid', $cart->event_uid)->first();
        $barcodeData =  QrCode::size(250)->generate($url) ;

        // Menampilkan tampilan dengan barcode
        return view('barcode', 
        [
            'barcodeData' => $barcodeData,
            'invoice' => $url,
            'event'=> $event
        ]);
    }
}
