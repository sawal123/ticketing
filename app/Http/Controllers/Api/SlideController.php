<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Event;
use App\Models\Slider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SlideController extends Controller
{
    public function slide()
    {
        $slide = Slider::orderBy('sort', 'asc')->get();
        // dd($slide);
        return response()->json([
            'title' => 'Landing',
            'slide' => $slide,
            'slider' => $slide,
        ]);
    }

    public function finishMidtrans()
    {
        $status_code = $_GET['status_code'];
        $invoice = $_GET['order_id'];
        // ?order_id=INV-05484&status_code=200&transaction_status=settlement
        $cart = Cart::where('invoice', $invoice)->first();
        // dd($cart);
        // dd(Auth::user()->email);
        $user = User::where('uid', $cart->user_uid)->first();

        if ($cart == null) {
            return redirect('/');
        }
        if ($status_code === '200') {
            return redirect('/detail-ticket/' . $cart->uid . '/' . $cart->user_uid)->with('success' , 'Selamat'. $user->name .'Pembayaran Berhasil.');
        }
    }
}
