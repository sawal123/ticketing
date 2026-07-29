<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Penarikan;
use App\Models\Slider;
use App\Models\Talent;
use App\Models\Term;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Services\SecureImageStorage;
use App\Services\Tickets\TicketReservationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeleteController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function deleteTalent($id)
    {
        $talent = Talent::where('uid', $id)->first();
        $talent->delete();

        return redirect()->back()->with('hapus', 'Talent Berhasil dihapus');
    }

    public function deteleListTransaksi($uid, $user_uid, TicketReservationService $reservationService)
    {
        $cart = Cart::where('uid', $uid)
            ->where('user_uid', Auth::user()->uid)
            ->first();

        if (! $cart) {
            return redirect()->back()->with('error', 'Transaksi tidak ditemukan');
        }

        DB::transaction(function () use ($cart, $reservationService) {
            $lockedCart = Cart::where('uid', $cart->uid)->lockForUpdate()->first();

            if ($lockedCart && in_array($lockedCart->status, Cart::ACTIVE_RESERVATION_STATUSES, true)) {
                $reservationService->releaseLockedCart($lockedCart, Cart::STATUS_CANCELLED);
            }
        }, 3);

        $transaction = Transaction::where('invoice', $cart->invoice)->first();
        if ($transaction) {
            $transaction->delete();
        }

        HargaCart::where('uid', $cart->uid)->get()->each->delete();

        $cart->delete();

        return redirect()->back()->with('deleteList', 'Check Out Berhasil dihapus');
    }

    public function deleteSlide($uid)
    {
        $slide = Slider::where('uid', $uid)->first();
        $this->images->delete('slide', $slide->gambar);
        $slide->delete();

        return redirect()->back()->with('deleteSlide', 'Slide Berhasil Dihapus');
    }

    public function deleteEvent($uid)
    {
        $event = Event::where('uid', $uid)->first();
        if ($event) {
            $this->images->delete('cover', $event->cover);
            $event->delete();
            $talent = Talent::where('uid', $event->uid)->get();
            if ($talent) {
                foreach ($talent as $talentItem) {
                    $this->images->delete('talent', $talentItem->gambar);
                    $talentItem->delete();
                }
            }
            $harga = Harga::where('uid', $event->uid)->get();
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
        $this->images->delete('user', $user->gambar);
        $user->delete();

        return redirect()->back()->with('deleteUser', 'User Berhasil Dihapus');
    }

    public function deleteCashes($uid)
    {

        $cashes = Cash::where('uid', $uid)->first();
        $cart = Cart::where('uid', $uid)->first();
        $transaksi = Transaction::where('uid', $uid)->first();
        $hargaCart = HargaCart::where('uid', $uid)->get();
        $cashes->delete();
        if ($hargaCart) {
            foreach ($hargaCart as $hc) {
                $hc->delete();
            }
        }
        if ($transaksi) {
            $transaksi->delete();
        }
        if ($cart) {
            $cart->delete();
        }
        if ($cashes) {
            $cashes->delete();
        }

        return redirect()->back()->with('success', 'Cashes Berhasil Dihapus');
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
        $this->images->delete('sosmed', $contact->icon);
        $contact->delete();

        return redirect()->back()->with('delete', 'Data berhasil dihapus');
    }

    public function deleteTransaksi($uid)
    {
        $transaksi = Transaction::where('uid', $uid)->first();
        $cart = Cart::where('uid', $uid)->first();
        $h_cart = HargaCart::where('uid', $uid)->first();
        if ($transaksi) {
            $transaksi->delete();
        }
        if ($cart) {
            $cart->delete();
        }
        if ($h_cart) {
            $h_cart->delete();
        }

        return redirect()->back()->with('delete', 'Transaksi berhasil dihapus');
    }
}
