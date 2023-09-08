<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function confir($data){
        $cart = Cart::where('invoice', $data)->first();
        $user = User::where('uid', $cart->user_uid)->first();

        return view('confir', [
            'cart' => $cart,
            'user' => $user
        ]);
    }
   
    
}
