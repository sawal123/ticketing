<?php

namespace App\Http\Middleware;

use App\Models\Contact;
use App\Models\Landing;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GlobalDataMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        error_reporting(0);
        $user = Auth::user();
        $csrfToken = csrf_token();
        $logo = Landing::all();
        $contact = Contact::all();
        // dd($logo);

        view()->share([
            
            'user'=> $user,
           'csrfToken' => $csrfToken,
           'cari'=> null,
           'logo' => $logo,
           'seo' => $logo,
           'contact'=> $contact
        ]);
        return $next($request);
    }
}
