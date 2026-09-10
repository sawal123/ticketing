<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Penarikan;
use App\Models\Slider;
use App\Models\Talent;
use App\Models\Term;
use App\Models\User;
use App\Models\Voucher;
use App\Services\SecureImageStorage;
use App\Services\Tickets\TicketReservationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function deleteTalent($id)
    {
        $talent = Talent::where('uid', $id)->first();
        $talent->delete();

        return redirect()->back()->with('hapus', 'Talent Berhasil dihapus');
    }

    public function cancelListTransaksi($uid, TicketReservationService $reservationService)
    {
        try {
            $reservationService->cancelOwnedReservation($uid, Auth::user()->uid);
        } catch (ValidationException $exception) {
            if (request()->expectsJson()) {
                throw $exception;
            }

            return redirect()->back()->with('error', collect($exception->errors())->flatten()->first());
        }

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Transaksi berhasil dibatalkan.']);
        }

        return redirect()->back()->with('deleteList', 'Transaksi berhasil dibatalkan');
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
        $coverToDelete = null;
        $talentImagesToDelete = [];

        try {
            DB::transaction(function () use ($uid, &$coverToDelete, &$talentImagesToDelete) {
                $event = Event::where('uid', $uid)->lockForUpdate()->first();

                if (! $event) {
                    return;
                }

                if (Agreement::query()
                    ->where('event_uid', $event->uid)
                    ->where('status', Agreement::STATUS_COMPLETED)
                    ->lockForUpdate()
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'event' => 'Event tidak dapat dihapus karena memiliki agreement yang sudah selesai.',
                    ]);
                }

                $coverToDelete = $event->cover;
                $event->delete();

                $talent = Talent::where('uid', $event->uid)->lockForUpdate()->get();
                foreach ($talent as $talentItem) {
                    $talentImagesToDelete[] = $talentItem->gambar;
                    $talentItem->delete();
                }

                $harga = Harga::where('uid', $event->uid)->lockForUpdate()->get();
                foreach ($harga as $hargaItem) {
                    $hargaItem->delete();
                }
            }, 3);
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->errors()['event'][0] ?? 'Event tidak dapat dihapus.');
        }

        if ($coverToDelete) {
            $this->images->delete('cover', $coverToDelete);
        }

        foreach ($talentImagesToDelete as $talentImage) {
            $this->images->delete('talent', $talentImage);
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
}
