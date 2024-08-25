<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TController extends Controller
{
    public function tonline(){
        return view('backend.transaksi.tonline');
    }
}
