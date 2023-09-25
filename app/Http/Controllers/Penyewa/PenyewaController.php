<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PenyewaController extends Controller
{
    public function index()
    {
        return view(
            'penyewa.page.dashboard',
            [
                'title' => 'Dashboard'
            ]
        );
    }
    public function login(){
        return  view('penyewa.auth.login', [
            'title'=>'Login',
        ]);
    }
}
