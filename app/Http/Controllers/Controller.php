<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\User;
use App\Models\HargaCart;
use App\Models\Penarikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function confir($data)
    {
        $cart = Cart::where('invoice', $data)->first();

        if ($cart === null) {
            return 'Tidak Ada Akses';
        }
        $user = User::where('uid', $cart->user_uid)->first();
        $hargaCart = HargaCart::where('uid', $cart->uid)->get();
        if (Auth::user()->role === 'admin') {

            return view('confir', [
                'cart' => $cart,
                'user' => $user,
                'harga' => $hargaCart
            ]);
        } else {
            echo 'Akses Ditolak';
        }
    }
    public function success(Request $request)
    {
        $req = $request->success;

        $cart = Cart::where('invoice', $req)->first();
        if ($cart->status === 'PAID') {
            $cart->konfirmasi = '1';
            $cart->save();
            return redirect()->back()->with('berhasil', 'Berhasil Dikonfirmasi');
        } else {
            return redirect()->back()->with('gagal', 'Konfirmasi Gagal');
        }
    }

    public function notif(){
        return view('email.notif-email');
    }


    public function invoice($uid = null){
       
        if($uid === null){
            return redirect()->back();
        }
        // dd($uid);
        $penarikan = Penarikan::join('users', 'users.uid', '=', 'penarikans.uid_user')
        ->select(
            'penarikans.uid',
            'penarikans.uid_user',
            'penarikans.amount',
            'penarikans.kwitansi',
            'penarikans.status',
            'penarikans.created_at',
            'penarikans.updated_at',
            'users.name',
            'users.email',
            'users.gambar'
        )
        ->where('penarikans.uid', $uid)
        ->get();
        dd($penarikan);
        return view('invoice',[
            'title'=>'Invoice'
        ]);
    }
}
