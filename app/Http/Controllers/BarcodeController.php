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
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class BarcodeController extends Controller
{

    public function showLogin($data)
    {
        if (Auth::check()) {
            return redirect()->route('barcode.generate', ['data' => $data]);
        }

        return view('barcode-login', [
            'data' => $data,
        ]);
    }

    public function login(Request $request, $data)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route('barcode.generate', ['data' => $data]);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Email atau password yang Anda masukkan salah.');
    }

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

        if (strtolower($cart->status) !== 'success') {
            return response()->view('barcode-error', [
                'message' => 'Barcode hanya bisa diakses untuk invoice dengan status success.',
            ], 403);
        }

        if (!Auth::check()) {
            return redirect()->route('barcode.login', ['data' => $data]);
        }

        if (!$this->userOwnsInvoice($cart)) {
            return response()->view('barcode-error', [
                'message' => 'Anda tidak memiliki akses ke invoice ini.',
            ], 403);
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

    private function userOwnsInvoice(Cart $cart): bool
    {
        $user = Auth::user();

        if ($cart->payment_type === 'cash') {
            return Cash::where('uid', $cart->uid)
                ->where('email', $user->email)
                ->exists();
        }

        return $user->uid === $cart->user_uid;
    }
}
