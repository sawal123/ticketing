<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Slider;

class SlideController extends Controller
{
    public function slide($data = null)
    {
        try {
            $query = Slider::select(['id', 'url', 'gambar'])->orderBy('sort', 'asc');

            if ($data !== null) {
                $query->where('id', $data);
            }

            $slide = $query->get();

            return response()->json([
                'slide' => $slide,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan dalam mengambil data slide.',
            ], 500); // 500 adalah kode status untuk kesalahan server.
        }
    }
}
