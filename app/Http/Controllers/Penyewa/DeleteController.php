<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Partner;
use App\Models\Talent;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\SecureImageStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteController extends Controller
{
    private const BLOCKING_TRANSACTION_STATUSES = [
        Cart::STATUS_SUCCESS,
        Cart::STATUS_PENDING,
        Cart::STATUS_RESERVED,
        Cart::STATUS_PAYMENT_REVIEW,
        Cart::STATUS_UNPAID,
    ];

    public function __construct(private SecureImageStorage $images) {}

    public function eventDelete($uid)
    {
        $coverToDelete = null;
        $talentImagesToDelete = [];

        try {
            DB::transaction(function () use ($uid, &$coverToDelete, &$talentImagesToDelete) {
                $event = $this->ownedEventQuery($uid)->lockForUpdate()->firstOrFail();

                if ($event->konfirmasi !== null || strtolower((string) $event->status) === 'active') {
                    throw ValidationException::withMessages([
                        'event' => 'Event yang sudah aktif/terkonfirmasi tidak dapat dihapus.',
                    ]);
                }

                $eventDate = EventDate::where('uid', $event->uid)->lockForUpdate()->first();
                $hargaEvent = Harga::where('uid', $event->uid)->lockForUpdate()->get();
                $talentEvent = Talent::where('uid', $event->uid)->lockForUpdate()->get();

                if ($hargaEvent->contains(fn (Harga $harga) => (int) ($harga->sold_qty ?? 0) > 0
                    || (int) ($harga->reserved_qty ?? 0) > 0)) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak dapat dihapus karena sudah memiliki tiket terjual atau reserved.',
                    ]);
                }

                $relatedCart = Cart::query()
                    ->where('event_uid', $event->uid)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if ($relatedCart) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak dapat dihapus karena sudah memiliki transaksi.',
                    ]);
                }

                if ($this->eventHasHistoricalRecords($event->uid)) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak dapat dihapus karena sudah memiliki riwayat transaksi.',
                    ]);
                }

                $coverToDelete = $event->cover;
                $event->forceDelete();

                if ($eventDate) {
                    $eventDate->delete();
                }

                foreach ($talentEvent as $talent) {
                    $talentImagesToDelete[] = $talent->gambar;
                    $talent->delete();
                }

                foreach ($hargaEvent as $harga) {
                    $harga->delete();
                }
            }, 3);
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->errors()['event'][0] ?? 'Event tidak dapat dihapus.');
        }

        $this->images->delete('cover', $coverToDelete);
        foreach ($talentImagesToDelete as $talentImage) {
            $this->images->delete('talent', $talentImage);
        }

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

        if ($this->hargaHasTransactions($harga)) {
            return redirect()->back()->with('error', 'Tiket tidak dapat dihapus karena sudah memiliki transaksi.');
        }

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

    private function eventHasHistoricalRecords(string $eventUid): bool
    {
        return HargaCart::query()->where('event_uid', $eventUid)->exists()
            || Transaction::query()->where('event_uid', $eventUid)->exists()
            || Cash::query()->where('uid_event', $eventUid)->exists();
    }

    private function hargaHasTransactions(Harga $harga): bool
    {
        return $harga->hargaCarts()
            ->whereHas('cart', fn ($query) => $query->whereIn('status', self::BLOCKING_TRANSACTION_STATUSES))
            ->exists();
    }
}
