<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use App\Mail\ForgotPassword;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\MidtransPaymentNotification;
use App\Models\ForgotPassword as ModelsForgotPassword;

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
    public function forgot(){
        return view('frontend.page.auth.forgot-password',[
            'title' => 'Forgot Password'
        ]);
    }
    public function email(Request $request){
        $email = $request->email;
        $user = User::where('email', $email)->first();
        if($user){
            $add = new ModelsForgotPassword([
                'uid'=> Str::random('10'),
                'uid_user' => $user->uid,
            ]);
            $add->save();
            Mail::to($user->email)->send(new ForgotPassword($user));
            return redirect()->back()->with('success', 'Periksa Email Anda, Atau Lihat Di Spam!');
        }
        else{
            return redirect()->back()->with('error', 'Email Tidak Terdaftar');
        }
      
    }

    public function resetPassword($data){
        $user = User::where('uid', $data)->first();
        if($user){
            return view('frontend.page.auth.reset-password', [
                'title' => 'Reset Password',
                'data' => $data
            ]);
        }
        else{
            abort('403');
        }  
    }
    public function newPassword(Request $request){
        $pass = $request->password;
        $data = $request->data;

        $user = User::where('uid', $data)->first();
        if($user){
            $user->password =  bcrypt($pass);
            $user->save();
            return redirect('/login');
        }
        
    }
}
