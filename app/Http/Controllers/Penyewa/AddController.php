<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Models\Bank;
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
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AddController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function addEvent(Request $request): RedirectResponse
    {
        return redirect()
            ->route('dashboard.event.create')
            ->with('error', 'Form event lama sudah ditutup. Gunakan form event baru.');
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
        $code = Str::upper(trim((string) $request->code));
        $request->merge(['code' => $code]);

        $validate = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/'],
            'unit' => 'string|required|max:255',
            // 'nominal' => 'numeric|required',
            'min' => 'required|numeric',
            'max' => 'numeric',
            'maxUse' => 'required|numeric',
        ]);
        $validate->validate();
        // dd($request->event);
        $event = Event::where('uid', $request->event)->where('user_uid', Auth::user()->uid)->firstOrFail();

        Validator::make(['code' => $code], [
            'code' => [
                Rule::unique('vouchers', 'code')
                    ->where(fn ($query) => $query->where('event_uid', $event->uid)),
            ],
        ], [
            'code.unique' => 'Kode voucher sudah digunakan pada event ini.',
        ])->validate();

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
            'code' => $code,
            'unit' => $request->unit,
            'nominal' => $nominal,
            'min_beli' => $request->min,
            'max_disc' => $request->max,
            'digunakan' => 0,
            'limit' => $request->maxUse,
            'status' => 'active',
        ]);

        try {
            $voucher->save();
        } catch (QueryException $e) {
            if ($this->isDuplicateVoucherCodeException($e)) {
                return redirect()->back()->withErrors(['code' => 'Kode voucher sudah digunakan pada event ini.']);
            }

            throw $e;
        }

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
                ] + $this->bankSnapshotFor($ownerUid));
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

    private function isDuplicateVoucherCodeException(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'vouchers_event_uid_code_unique')
            || str_contains($e->getMessage(), 'UNIQUE constraint failed')
            || str_contains($e->getMessage(), 'Duplicate entry');
    }

    private function bankSnapshotFor(string $uid): array
    {
        if (! Schema::hasColumn('penarikans', 'bank_name')) {
            return [];
        }

        $bank = Bank::where(function ($q) use ($uid) {
            $q->where('uid_user', $uid)
                ->orWhere('uid', $uid);
        })->latest()->first();

        if (! $bank) {
            return [
                'bank_name' => null,
                'bank_account_name' => null,
                'bank_account_number' => null,
            ];
        }

        return [
            'bank_name' => $bank->bank,
            'bank_account_name' => $bank->nama,
            'bank_account_number' => $bank->norek,
        ];
    }
}
