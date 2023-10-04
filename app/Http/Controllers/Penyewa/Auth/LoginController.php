<?php

namespace App\Http\Controllers\Penyewa\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index(Request $request){
        $user = $request->only('email', 'password');
        if (Auth::attempt($user)) {
            if(Auth::user()->role === 'penyewa'){
                return redirect('/dashboard');
            }
            else{
                return redirect()->back()->with('success', 'Email atau password salah.');
            }
        } else {
            return redirect()->back()->with('success', 'Email atau password salah.');
        }
   
    }
}
