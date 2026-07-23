<?php

namespace App\Http\Middleware;

use App\Models\Contact;
use App\Models\Landing;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $logo = Cache::remember('global_data.logo', now()->addMinutes(10), fn () => Landing::all());
        $sosmed = Cache::remember('global_data.sosmed', now()->addMinutes(10), fn () => Contact::where('icon', '!=', 'null')->get());
        $contact = Cache::remember('global_data.contact', now()->addMinutes(10), fn () => Contact::where('icon', 'null')->get());
        // 'contact'=>$contact
        // dd($logo);

        view()->share([
            
            'user'=> $user,
           'csrfToken' => $csrfToken,
           'cari'=> null,
           'logo' => $logo,
           'seo' => $logo,
           'contact'=> $sosmed,
           'contactus'=> $contact,
        ]);
        return $next($request);
    }
}
