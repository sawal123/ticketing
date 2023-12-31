<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Harga;
use App\Models\Talent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Partner;

class DeleteController extends Controller
{
    public function deleteTalent($id)
    {
        $talent = Talent::where('uid', $id)->first();
        $talent->delete();
        return redirect()->back()->with('hapus', 'Talent Berhasil dihapus');
    }
    public function deleteHarga($uid)
    {
        $harga = Harga::where('id', $uid)->first();
        $harga->delete();
        return redirect()->back()->with('deleteHarga', 'Harga Berhasil Dihapus');
    }

    public function deletePartner($uid){
        $partner = Partner::where('uid', $uid)->first();
        $partner->delete();
        return redirect()->back()->with('success', 'Partner Berhasil dihapus');
    }
}
