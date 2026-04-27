<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Event;
use App\Models\Slider;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SlideController extends Controller
{
    public function slide($data = null)
    {
        try {
        $query = Slider::select(['id','url', 'gambar'])->orderBy('sort', 'asc');

        if ($data !== null) {
            $query->where('id', $data);
        }

        $slide = $query->get();

        return response()->json([
            'slide' => $slide,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Terjadi kesalahan dalam mengambil data slide.'
        ], 500); // 500 adalah kode status untuk kesalahan server.
    }
    }

    }
}