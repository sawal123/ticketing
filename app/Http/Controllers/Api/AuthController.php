<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
     public function login(Request $request){
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            if($user->role === 'penyewa'){
                $success['token'] = $user->createToken($request->email)->plainTextToken;
                $success['name'] = $user->name; 
                $success['uid'] = $user->uid; 
                $success['email'] = $user->email; 
                return response()->json(['success' => true, 'data' => $success,], 200);
            }
            else{
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
           
        } else {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
    }
}
