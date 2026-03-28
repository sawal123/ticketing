<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Landing;
// use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function getLandingData()
    {
        // Ambil baris pertama dari tabel landings
        $landing = Landing::first();

        // Jika tabel kosong
        if (!$landing) {
            return response()->json([
                'success' => false,
                'message' => 'Data landing page belum diatur di database.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data landing page berhasil dimuat',
            'data' => [
                'description' => $landing->description,
                'keyword' => $landing->keyword,
                // Mengubah nama file menjadi Full URL agar siap pakai di Vue
                'logo' => $landing->logo ? url('storage/logo/' . $landing->logo) : null,
                // Asumsi icon disimpan di folder storage/icon/, sesuaikan jika foldernya beda
                'icon' => $landing->icon ? url('storage/logo/' . $landing->icon) : null,
            ]
        ], 200);
    }
}
