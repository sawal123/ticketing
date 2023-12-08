<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Cart;
use App\Models\Term;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Slider;
use App\Models\Talent;
use App\Models\Contact;
use App\Models\Voucher;
use App\Models\HargaCart;
use App\Models\Penarikan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
        $event = Event::where('uid', $uid)->first();
        if ($event) {
            $event->delete();
            $talent = Talent::where('uid', $event->uid)->get();
            if ($talent) {
                foreach ($talent as $talentItem) {
                    $talentItem->delete();
                }
            }
            $harga = Talent::where('uid', $event->uid)->get();
            if ($harga) {
                foreach ($harga as $hargaItem) {
                    $hargaItem->delete();
                }
            }
        }

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
    public function deleteUser($uid)
    {
        $user = User::where('uid', $uid)->first();
        // dd($user);
        $user->delete();
        return redirect()->back()->with('deleteUser', 'User Berhasil Dihapus');
    }
    public function deleteVoucher($uid)
    {
        $voucher = Voucher::where('uid', $uid)->first();
        $voucher->delete();
        return redirect()->back()->with('deleteVoucher', 'Voucher Berhasil Dihapus');
    }
    public function deletePenarikan($uid)
    {
        // dd($uid);
        $penarikan = Penarikan::where('uid', $uid)->first();
        $penarikan->delete();
        return redirect()->back()->with('delete', 'Data berhasil dihapus');
    }
    public function deleteContact($id)
    {
        // dd($uid);
        $contact = Contact::where('id', $id)->first();
        $contact->delete();
        return redirect()->back()->with('delete', 'Data berhasil dihapus');
    }
}
