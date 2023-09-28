<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        $requestedUrl = $request->getRequestUri();
        // dd(strpos($requestedUrl, '/admin'));
        // return  ? null : route('login');

        if($request->expectsJson()){
            return null;
        }
        elseif(strpos($requestedUrl, '/admin') !== false){
            return route('login');
        }
        elseif(strpos($requestedUrl, '/dashboard') !== false){
            return route('signIn');
        }
        else{
            return route('home');
        }


    }
}
