<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UserLoginController extends Controller
{
    public function signIn()
    {
        if(Auth::user()){
            return redirect('/');
        }
        else{
            return view('frontend.page.auth.signin', [
                'title' => 'Login'
            ]);
        }
        
    }
    public function loginUser(Request $request)
    {
        $user = $request->only('email', 'password');

        if (Auth::attempt($user)) {
            if(Auth::user()->role === 'admin'){
                return redirect('/admin');
            }
            else{
                return redirect('/');
            }
        } else {
            return redirect('/login')->with('success', 'Email atau password salah.');
        }
    }
}
