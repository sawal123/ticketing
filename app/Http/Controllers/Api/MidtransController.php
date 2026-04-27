<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    /**
     * Handle Midtrans Finish Redirect URL
     */
    public function finish(Request $request)
    {
        $status_code = $request->query('status_code');
        $invoice = $request->query('order_id');

        // ?order_id=INV-05484&status_code=200&transaction_status=settlement
        
        $cart = Cart::where('invoice', $invoice)->first();

        if (!$cart) {
            return redirect('/');
        }

        if ($cart->user_uid === null) {
            return redirect()->back();
        }

        $user = User::where('uid', $cart->user_uid)->first();
        $userName = $user ? $user->name : '';

        if ($status_code === '200') {
            return redirect('/detail-ticket/' . $cart->uid . '/' . $cart->user_uid)
                ->with('success', 'Selamat ' . $userName . ', Pembayaran Berhasil.');
        }

        if ($status_code === '201') {
            return redirect('/detail-ticket/' . $cart->uid . '/' . $cart->user_uid)
                ->with('success', 'Hi ' . $userName . ', Pembayaran Kamu Masih Pending Nih.');
        }

        return redirect('/detail-ticket/' . $cart->uid . '/' . $cart->user_uid);
    }

    /**
     * Handle Midtrans Pending Redirect URL
     */
    public function pending(Request $request)
    {
        $invoice = $request->query('order_id');
        $cart = Cart::where('invoice', $invoice)->first();

        if ($cart) {
            return redirect('/detail-ticket/' . $cart->uid . '/' . $cart->user_uid)
                ->with('success', 'Pembayaran Kamu Sedang Diproses.');
        }

        return redirect('/');
    }
}
