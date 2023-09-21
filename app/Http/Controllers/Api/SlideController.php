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
    public function slide($data = null)
    {
        try {
        $query = Slider::select(['id','url', 'gambar'])->orderBy('sort', 'asc');

        if ($data !== null) {
            $query->where('id', $data);
        }

        $slide = $query->get();

        return response()->json([
            'slide' => $slide,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Terjadi kesalahan dalam mengambil data slide.'
        ], 500); // 500 adalah kode status untuk kesalahan server.
    }
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
        if($status_code === '201'){
            return redirect('/detail-ticket/' . $cart->uid . '/' . $cart->user_uid)->with('success' , 'Hi'. $user->name .'Pembayaran Kamu Masih Pending Nih.');
        }
    }
    public function pendingMidtrans(){
        $order_id = $_GET['order_id'];
        $status_code = $_GET['status_code'];
        $transaction_status = $_GET['transaction_status'];


    }
}