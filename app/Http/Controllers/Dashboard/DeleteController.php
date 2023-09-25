<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Cart;
use App\Models\Talent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Slider;
use App\Models\Term;
use App\Models\User;

class DeleteController extends Controller
{
    public function deleteTalent($id)
    {
        $talent = Talent::where('uid', $id)->first();
        $talent->delete();
        return redirect()->back()->with('hapus', 'Talent Berhasil dihapus');
    }
    public function deteleListTransaksi($uid, $user_uid)
    {
        $talent = Cart::with(['users'])->where('uid', $uid)->first();
        $hargaCart = HargaCart::with(['carts'])->where('uid', $talent->uid)->first();
        // dd($talent);
        $talent->delete();
        $hargaCart->delete();
        return redirect()->back()->with('deleteList', 'Check Out Berhasil dihapus');
    }

    public function deleteSlide($uid)
    {
        $slide = Slider::where('uid', $uid)->first();
        $slide->delete();
        return redirect()->back()->with('deleteSlide', 'Slide Berhasil Dihapus');
    }

    public function deleteEvent($uid)
    {
        $event = Event::with(['harga', 'talent'])->where('uid', $uid)->first();
        $event->delete();
        $event->harga->delete();
        $event->talent->delete();
        return redirect()->back()->with('deleteEvent', 'Event Berhasil Dihapus');
    }

    public function deleteHarga($uid)
    {
        $harga = Harga::where('id', $uid)->first();
        $harga->delete();
        return redirect()->back()->with('deleteHarga', 'Harga Berhasil Dihapus');
    }

    public function deleteTerm($uid)
    {
        $term = Term::where('uid', $uid)->first();
        $term->delete();
        return redirect()->back()->with('deleteTerm', 'Term Berhasil Dihapus');
    }
    public function deleteUser($uid){
        $user = User::where('uid', $uid)->first();
        // dd($user);
        $user->delete();
        return redirect()->back()->with('deleteUser', 'User Berhasil Dihapus');
    }
}
