<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use App\Models\HargaCart;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ConfirmController extends Controller
{
    //
    public function cekData($data = null)
    {
        if ($data !== null) {
            $cart = Cart::select(['carts.uid', 'carts.user_uid', 'carts.event_uid', 'carts.invoice', 'carts.konfirmasi', 'carts.status',DB::raw('SUM(harga_carts.quantity) as qty')])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->where('carts.invoice' , $data)
            ->groupBy('carts.uid','carts.user_uid','carts.event_uid', 'carts.invoice', 'carts.status', 'carts.konfirmasi')
            ->first();

            if ($cart !== null && $cart->status === 'SUCCESS') {
                $user = User::select(['uid', 'name'])->where('uid', $cart->user_uid)->first();
                $event = Event::select(['event'])->where('uid', $cart->event_uid)->first();
                $harga = HargaCart::select(['quantity', 'kategori_harga'])->where('uid', $cart->uid)->get();

                $tes = [];
                foreach ($harga as $hargas) {
                    $tes[] = $hargas;
                }
                return response()->json([
                    'cart' => $cart,
                    'event' => $event,
                    'user' => $user,
                    'harga' => $tes
                ], 200);
            } else {
                echo "Akses Di Tolak";
            }
        } else {
            echo "Tidak Ada Data";
        }
    }

    public function upKonfirmasi(Request $request,  $data)
    {
        $req = $request->konfirmasi;
        // dd($req);
        if ($data !== null) {
            $cart = Cart::where('invoice', $data)->first();
            if ($cart->konfirmasi === null) {
                $cart->konfirmasi = "1";
                $cart->save();
                return response()->json([
                    'carts' => $cart
                ], 200);
                // return redirect('api/confirm/' . $data);
            } else {
                // Data tidak ditemukan, kirim respons 404
                return response()->json([
                    'message' => 'Data Tidak Ditemukan',
                ], 404);
            }
        } else {
            return response()->json([
                'message' => 'Invoice harus diberikan',
            ], 400);
        }
    }

    public function verfikasi($data){
        $data;
        $cart = Cart::select(['carts.uid', 'carts.konfirmasi', 'events.event', 'users.name', 'users.gambar'])
        ->where('event_uid', $data)
        ->where('carts.konfirmasi', '1')
        ->join('events', 'events.uid', '=', 'carts.event_uid')
        ->join('users', 'users.uid', '=', 'carts.user_uid')
        ->get();
        if($data !== null){
            return response()->json([
                'cart' =>$cart,
                
            ],200);
        }

    }

    public function listEvent(Request $request){
        $event = Event::where('user_uid', Auth::user()->uid)->get();
        return response()->json([
            'event'=>$event,
        ], 200);
    }
}
