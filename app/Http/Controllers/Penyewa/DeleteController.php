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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeleteController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function eventDelete($uid)
    {
        DB::transaction(function () use ($uid) {
            $event = $this->ownedEventQuery($uid)->lockForUpdate()->firstOrFail();
            $eventDate = EventDate::where('uid', $event->uid)->lockForUpdate()->first();
            $hargaEvent = Harga::where('uid', $event->uid)->lockForUpdate()->get();
            $talentEvent = Talent::where('uid', $event->uid)->lockForUpdate()->get();

            $this->images->delete('cover', $event->cover);
            $event->forceDelete();

            if ($eventDate) {
                $eventDate->delete();
            }

            foreach ($talentEvent as $talent) {
                $this->images->delete('talent', $talent->gambar);
                $talent->delete();
            }

            foreach ($hargaEvent as $harga) {
                $harga->delete();
            }
        });

        return redirect()->back()->with('hapus', 'Data Event Berhasil dihapus');
    }

    public function deleteTalent($id)
    {
        $talentEvent = $this->ownedTalentQuery($id)->firstOrFail();
        $this->images->delete('talent', $talentEvent->gambar);
        $talentEvent->delete();

        return redirect()->back()->with('hapus', 'Talent Berhasil dihapus');
    }

    public function deleteHarga($uid)
    {
        $harga = $this->ownedHargaQuery($uid)->firstOrFail();
        $harga->delete();

        return redirect()->back()->with('deleteHarga', 'Harga Berhasil Dihapus');
    }

    public function deletePartner($uid)
    {
        $partner = $this->ownedPartnerQuery($uid)->firstOrFail();
        $partner->delete();

        return redirect()->back()->with('success', 'Partner Berhasil dihapus');
    }

    public function deleteVoucher($uid)
    {
        $voucher = $this->ownedVoucherQuery($uid)->firstOrFail();
        $voucher->delete();

        return redirect()->back()->with('success', 'Voucher Berhasil dihapus');
    }

    private function ownerUid(): string
    {
        return Auth::user()->uid;
    }

    private function ownedEventQuery(string $uid)
    {
        return Event::where('uid', $uid)->where('user_uid', $this->ownerUid());
    }

    private function ownedTalentQuery(string $id)
    {
        return Talent::query()
            ->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('uid', $id);
            })
            ->whereHas('event', fn ($query) => $query->where('user_uid', $this->ownerUid()));
    }

    private function ownedHargaQuery(string $id)
    {
        return Harga::query()
            ->where('id', $id)
            ->whereHas('event', fn ($query) => $query->where('user_uid', $this->ownerUid()));
    }

    private function ownedPartnerQuery(string $uid)
    {
        return Partner::query()
            ->where('uid', $uid)
            ->where(function ($query) {
                $query->where('user_uid', $this->ownerUid())
                    ->orWhereHas('event', fn ($event) => $event->where('user_uid', $this->ownerUid()));
            });
    }

    private function ownedVoucherQuery(string $uid)
    {
        return Voucher::query()
            ->where('uid', $uid)
            ->where(function ($query) {
                $query->where('user_uid', $this->ownerUid())
                    ->orWhereHas('event', fn ($event) => $event->where('user_uid', $this->ownerUid()));
            });
    }
}
