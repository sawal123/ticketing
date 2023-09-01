<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Cart;
use App\Models\Talent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DeleteController extends Controller
{
    public function deleteTalent($id){
        $talent = Talent::where('uid', $id)->first();
        $talent->delete();
        return redirect()->back()->with('hapus','Talent Berhasil dihapus');
    }
    public function deteleListTransaksi($uid, $user_uid){
        $talent = Cart::with(['users'])->where('uid', $uid)->first();
        // dd($talent);
        $talent->delete();
        return redirect()->back()->with('deleteList','Check Out Berhasil dihapus');
    }
}
