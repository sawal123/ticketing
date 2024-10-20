<?php

namespace App\Http\Controllers;

use DNS2D;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\User;
use App\Models\Event;
use Milon\Barcode\DNS1D;
use App\Models\HargaCart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class BarcodeController extends Controller
{


    public function generateBarcode(Request $request, $data)
    {
        // $url = url('confir/data/'.$request->barcode);
        // $url = $request->barcode;
        if(!$data){
            return abort(404, 'not found');;
        }
        $url = $data;
        $cart = Cart::where('invoice', $url)->first();

        if(!$cart){
            return abort(404, 'not found');
        }

        $hargaC = HargaCart::where('uid', $cart->uid)->get();
        $event = Event::where('uid', $cart->event_uid)->first();
        $barcodeData =  QrCode::size(250)->generate($url);

        if($cart->payment_type === 'cash'){
            $user = Cash::where('uid', $cart->uid)->first();
        }else{
            $user = User::where('uid', $cart->user_uid)->first();
        }

        return view('barcode',
            [
                'barcodeData' => $barcodeData,
                'invoice' => $url,
                'event' => $event,
                'hargaC' =>$hargaC,
                'userBarcode' =>$user
            ]
        );
    }
}
