<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Harga;
use App\Models\Partner;
use App\Models\Talent;
use App\Models\Voucher;
use App\Services\SecureImageStorage;

class DeleteController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function eventDelete($uid)
    {
        $event = Event::where('uid', $uid)->first();
        $eventDate = EventDate::where('uid', $uid)->first();
        $hargaEvent = Harga::where('uid', $uid)->get();
        $talentEvent = Talent::where('uid', $uid)->get();

        $this->images->delete('cover', $event->cover);
        $event->forceDelete();
        if ($eventDate) {
            $eventDate->delete();
        }
        if ($talentEvent) {
            foreach ($talentEvent as $talent) {
                $this->images->delete('talent', $talent->gambar);
                $talent->delete();
            }
        }
        if ($hargaEvent) {
            foreach ($hargaEvent as $harga) {
                $harga->delete();
            }
        }

        return redirect()->back()->with('hapus', 'Data Event Berhasil dihapus');
    }

    public function deleteTalent($id)
    {
        $talentEvent = Talent::where('uid', $id)->first();
        $this->images->delete('talent', $talentEvent->gambar);
        $talentEvent->delete();

        return redirect()->back()->with('hapus', 'Talent Berhasil dihapus');
    }

    public function deleteHarga($uid)
    {
        $harga = Harga::where('id', $uid)->first();
        $harga->delete();

        return redirect()->back()->with('deleteHarga', 'Harga Berhasil Dihapus');
    }

    public function deletePartner($uid)
    {
        $partner = Partner::where('uid', $uid)->first();
        $partner->delete();

        return redirect()->back()->with('success', 'Partner Berhasil dihapus');
    }

    public function deleteVoucher($uid)
    {
        $voucher = Voucher::where('uid', $uid)->first();
        $voucher->delete();

        return redirect()->back()->with('success', 'Voucher Berhasil dihapus');
    }
}
