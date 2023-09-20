<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserRegisterController extends Controller
{

    public function index()
    {
       if(Auth::check()){
        return redirect('/');
       }
       else{
        return view('frontend.page.auth.register', [
            'title' => 'Register',
        ]);
    }
       }

    public function create(Request $request)
    {
        $uid = Str::random(10);
        
        // dd(User::USER_ROLE);
        $validateUser = $request->validate([
            'user' => 'required|max:255',
            'email' => 'required|email',
            'nomor' => 'required|numeric',
            'gender' => 'required|max:10',
            'password' => 'required|min:8',
        ]);
        // dd($validateUser['user']);
        if (User::where('email', $validateUser['email'])->exists()) {
            return redirect()->back()->with('error','Email Sudah Terdaftar!'); // Ganti 'email.exists.page' dengan nama route yang sesuai
        }
        
        $user = User::create([
            'uid' => $uid,
            'name' => $validateUser['user'],
            'email' => $validateUser['email'],
            'nomor' => $validateUser['nomor'],
            'birthday'=> '',
            'alamat' => '',
            'kota'=>'',
            'gender' => $validateUser['gender'],
            'gambar' => '',
            'role' => User::USER_ROLE,
            'password' => Hash::make($validateUser['password'])
        ]);
        $success['token'] = $user->createToken('auth_token')->plainTextToken;
        $success['name'] = $user->name;
        // dd($user);
        return redirect('/login')->with('success', 'Registrasi Berhasil'); 


        // try {
        //     // $user->save();
           
        // } catch (\Exception $e) {
        //     // Tangani kesalahan (misalnya: tampilkan pesan kesalahan atau log)
        //     return back()->withInput()->withErrors(['success' => 'Terjadi kesalahan dalam menyimpan data.']);
        // }

      
    }
}
