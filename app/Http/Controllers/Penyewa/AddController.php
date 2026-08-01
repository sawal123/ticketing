<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Harga;
use App\Models\Partner;
use App\Models\Penarikan;
use App\Models\Talent;
use App\Models\User;
use App\Models\Voucher;
use App\Services\SecureImageStorage;
use App\Services\Withdrawals\WithdrawalBalanceService;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AddController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function addEvent(Request $request): RedirectResponse
    {
        $validate = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            'fee' => 'required|numeric|min:0|max:100', // Tambahkan validasi fee
            'alamat' => 'required|string|max:255',
            'start' => 'required|string',
            'end' => 'required|string',
            'map' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'cover' => SecureImageStorage::rules(),
        ]);
        $validate->validate();

        $uid = Str::uuid();
        // dd($uid);

        $startEvent = new EventDate([
            'uid' => $uid,
            'start' => $request->start,
            'end' => $request->end,
        ]);

        $event = new Event([
            'uid' => $uid,
            'user_uid' => Auth::user()->uid,
            'event' => $request->event,
            'alamat' => $request->alamat,
            'tanggal' => $request->start,
            'status' => 'active',
            'fee' => $request->fee, // AMBIL DARI INPUT
            'deskripsi' => $request->deskripsi,
            'map' => $request->map,
            'slug' => Str::slug($request->event),
            'konfirmasi' => null,
        ]);
        if ($request->hasFile('cover')) {
            $event['cover'] = $this->images->storeBasename($request->file('cover'), 'cover');
        }

        try {
            DB::beginTransaction();
            $event->save();
            $startEvent->save();
            DB::commit();

            return redirect('dashboard/event/eventDetail/'.$uid)->with('addEvent', 'Event Berhasil Disimpan..');
        } catch (Exception $e) {
            DB::rollback();

            return redirect()->back()->with('error', 'Tambah Event Gagal. Silahkan coba lagi.');
        }
    }

    public function addTalent(Request $request)
    {
        $request->validate([
            'gambar' => SecureImageStorage::rules(),
        ]);
        $event = Event::where('uid', $request->uid)->where('user_uid', Auth::user()->uid)->firstOrFail();

        $talent = new Talent([
            'uid' => $event->uid,
            'talent' => $request->talent,
        ]);
        if ($request->hasFile('gambar')) {
            $talent['gambar'] = $this->images->storeBasename($request->file('gambar'), 'talent');
        }
        $talent->save();

        return redirect()->back()->with('talent', 'Talent Berhasil disimpan');
    }

    public function addHarga(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
            'kategori' => 'required|string|max:255',
            'qty' => 'required|integer|min:0',
            'harga' => 'required|integer|min:0',
        ]);

        $eventOwner = Event::where('uid', $request->uid)->where('user_uid', Auth::user()->uid)->firstOrFail();
        // dd($request->qty);
        $event = Harga::where('kategori', $request->kategori)->where('uid', $eventOwner->uid)->first();
        // dd($event);
        if ($event === null) {
            $harga = new Harga([
                'uid' => $eventOwner->uid,
                'kategori' => $request->kategori,
                'qty' => (int) $request->qty,
                'harga' => (int) $request->harga,
                'status' => 'active',
            ]);
            try {
                $harga->save();

                return redirect()->back()->with('harga', 'Harga berhasil disimpan');
            } catch (Exception $e) {
                return redirect()->back()->with('deleteHarga', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('deleteHarga', 'Nama Ticket Tidak Boleh Sama!');
        }
    }

    public function addVoucher(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'code' => 'string|required|max:50',
            'unit' => 'string|required|max:255',
            // 'nominal' => 'numeric|required',
            'min' => 'required|numeric',
            'max' => 'numeric',
            'maxUse' => 'required|numeric',
        ]);
        $validate->validate();
        // dd($request->event);
        $event = Event::where('uid', $request->event)->where('user_uid', Auth::user()->uid)->firstOrFail();
        if ($request->unit === 'rupiah') {
            $nominal = $request->nominalRupiah;
        } else {
            $nominal = $request->nominalPersen;
        }
        $uid = Str::uuid();

        $voucher = new Voucher([
            'uid' => $uid,
            'user_uid' => Auth::user()->uid,
            'event_uid' => $event->uid,
            'code' => $request->code,
            'unit' => $request->unit,
            'nominal' => $nominal,
            'min_beli' => $request->min,
            'max_disc' => $request->max,
            'digunakan' => 0,
            'limit' => $request->maxUse,
            'status' => 'active',
        ]);
        $voucher->save();

        return redirect()->back()->with('voucher', 'Voucher berhasil disimpan');
    }

    public function addPenarikan(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'integer', 'min:10000', 'max:100000000'],
        ]);

        $ownerUid = Auth::user()->uid;
        $amount = (int) $request->amount;

        try {
            DB::transaction(function () use ($ownerUid, $amount) {
                User::where('uid', $ownerUid)->lockForUpdate()->firstOrFail();

                $availableBalance = app(WithdrawalBalanceService::class)
                    ->availableBalanceFor($ownerUid);

                if ($availableBalance < 1 || $amount > $availableBalance) {
                    return back()
                        ->with('error', 'Saldo Anda tidak mencukupi.')
                        ->throwResponse();
                }

                Penarikan::create([
                    'uid' => (string) Str::uuid(),
                    'uid_user' => $ownerUid,
                    'amount' => $amount,
                    'note' => 'Penarikan',
                    'kwitansi' => $availableBalance,
                    'status' => 'PENDING',
                ]);
            }, 3);

            return redirect()->back()->with('penarikan', 'Penarikan berhasil diajukan');
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Pengajuan Gagal!');
        }
    }

    public function addPartner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referensi' => 'string|max:255',
            'name' => 'string|required',
            'email' => 'string|email',
            'city' => 'string|required',
            'alamat' => 'string|required',
            'nomor' => 'numeric|required',
        ]);
        $validator->validate();
        // dd(Str::uuid());
        $partner = new Partner;
        $partner->uid = Str::uuid();
        $partner->user_uid = Auth::user()->uid;
        $partner->referensi = $request->input('referensi');
        $partner->name = $request->input('name');
        $partner->email = $request->input('email');
        $partner->hp = $request->input('nomor');
        $partner->city = $request->input('city');
        $partner->alamat = $request->input('alamat');
        $partner->status = 'active';

        try {
            $partner->save();

            return redirect()->back()->with('success', 'Partner Berhasil Ditambah');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
