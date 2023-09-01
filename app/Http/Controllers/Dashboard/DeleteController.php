<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Talent;
use Illuminate\Http\Request;

class DeleteController extends Controller
{
    public function deleteTalent($id){
        $talent = Talent::where('uid', $id)->first();
        $talent->delete();
        return redirect()->back()->with('hapus','Talent Berhasil dihapus');
    }
}
