<?php

namespace App\Http\Controllers\Penyewa;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Harga;
use App\Models\Partner;
use App\Models\Talent;
use App\Models\User;
use App\Models\Voucher;
use App\Services\SecureImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EditController extends Controller
{
    private const LOCKED_QTY_STATUSES = [
        Cart::STATUS_SUCCESS,
        Cart::STATUS_RESERVED,
        Cart::STATUS_PENDING,
        Cart::STATUS_PAYMENT_REVIEW,
        Cart::STATUS_UNPAID,
    ];

    public function __construct(private SecureImageStorage $images) {}

    public function editEventPenyewa(Request $request)
    {
        $request->validate([
            'fee' => 'required|numeric|min:0|max:100',
            'cover' => SecureImageStorage::rules(),
        ]);
        $event = $this->ownedEventQuery($request->uid)->firstOrFail();
        $eventDate = EventDate::where('uid', $event->uid)->firstOrFail();

        $tanggal = date('Y-m-d H:i', strtotime($request->tanggal));
        $event->event = $request->event;
        $event->alamat = $request->alamat;
        $event->tanggal = $tanggal;
        $event->fee = $request->fee; // TAMBAHKAN INI UNTUK MENGUPDATE FEE
        $eventDate->start = $request->start;
        $eventDate->end = $request->end;
        $event->status = $request->status;
        $event->deskripsi = $request->deskripsi;
        $event->map = $request->map;
        $event->slug = Str::slug($request->event);

        $oldCover = null;
        if ($request->hasFile('cover')) {
            $oldCover = $event->cover;
            $event->cover = $this->images->storeBasename($request->file('cover'), 'cover');
        }

        $eventDate->save();
        $event->save();
        $this->images->delete('cover', $oldCover);

        return redirect('/dashboard/event/eventDetail/'.$request->uid)->with('success', 'Berhasil di Update');
    }

    public function editTalent(Request $request)
    {
        $request->validate([
            'gambar' => SecureImageStorage::rules(),
        ]);

        $uid = $request->uid;
        $talent = $request->talent;

        $talents = $this->ownedTalentQuery($uid)->firstOrFail();
        $talents->talent = $talent;

        $oldImage = null;
        if ($request->hasFile('gambar')) {
            $oldImage = $talents->gambar;
            $talents->gambar = $this->images->storeBasename($request->file('gambar'), 'talent');
        }
        $talents->save();
        $this->images->delete('talent', $oldImage);

        return redirect()->back()->with('success', 'Berhasil di Update');
    }

    public function editHarga(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'kategori' => 'required|string|max:255',
            'qty' => 'required|integer|min:0',
            'harga' => 'required|integer|min:0',
        ]);

        $id = $request->id;
        $harga = $this->ownedHargaQuery($id)->firstOrFail();

        if ((int) $request->qty < $this->minimumLockedQty($harga)) {
            return redirect()->back()->with('error', 'Qty tiket tidak boleh lebih kecil dari jumlah tiket yang sudah terjual atau sedang dipesan.');
        }

        // dd($request->kategori);
        $harga->update([
            'kategori' => $request->kategori,
            'qty' => (int) $request->qty,
            'harga' => (int) $request->harga,
        ]);

        return redirect()->back()->with('editHarga', 'Harga Berhasil Di Ubah');
    }

    public function editRekening(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'nama' => 'required|string|max:255',
            'bank' => 'required|string|max:100',
            'norek' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
        ]);

        $user = User::where('uid', Auth::user()->uid)->firstOrFail();

        if (! Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors([
                'current_password' => 'Password konfirmasi tidak sesuai.',
            ]);
        }

        $rek = Bank::where(function ($query) use ($user) {
            $query->where('uid_user', $user->uid)
                ->orWhere('uid', $user->uid);
        })->first() ?? new Bank;
        $rek->uid = $user->uid;
        $rek->uid_user = $user->uid;
        $rek->nama = $request->nama;
        $rek->bank = $request->bank;
        $rek->norek = $request->norek;
        $rek->save();

        return redirect()->back()->with('editRek', 'Rekening Berhasil Di Update');
    }

    public function editProfile(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nomor' => 'nullable|string|max:30',
            'date' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'provinsi' => 'nullable|string|max:100',
            'alamat' => 'nullable|string|max:500',
            'img' => SecureImageStorage::rules(),
        ]);

        $user = User::where('uid', Auth::user()->uid)->firstOrFail();

        $user->name = $request->nama;
        $user->nomor = $request->nomor ?? $user->nomor;
        $user->birthday = $request->date ?? $user->birthday;
        $user->gender = $request->gender ?? $user->gender;
        $user->kota = $request->provinsi ?? $user->kota;
        $user->alamat = $request->alamat ?? $user->alamat;

        $oldImage = null;
        if ($request->hasFile('img')) {
            $oldImage = $user->gambar;
            $user->gambar = $this->images->storeBasename($request->file('img'), 'user');
        }

        try {
            $user->save();
            $this->images->delete('user', $oldImage);

            return redirect()->back()->with('editProfile', 'Informasi Berhasil Di Update');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal Update Profile. Silahkan coba lagi.');
        }
    }

    public function editPartner(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'string|required',
            'email' => 'string|required',
            'city' => 'string|required',
            'alamat' => 'string|required',
            'nomor' => 'numeric|required',
        ]);

        $validate->validate();
        $partner = $this->ownedPartnerQuery($request->uid)->firstOrFail();
        // dd($partner);

        $partner->name = $request->name;
        $partner->email = $request->email;
        $partner->city = $request->city;
        $partner->alamat = $request->alamat;
        $partner->hp = $request->nomor;

        try {
            $partner->save();

            return redirect()->back()->with('success', 'Partner Berhasil Diubah');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal Diubah');
        }
    }

    public function editVoucher(Request $request)
    {
        // Validasi input dari form
        $validate = Validator::make($request->all(), [
            'code' => 'string|required|max:50',
            'unit' => 'string|required|max:255',
            'min' => 'required|numeric',
            'max' => 'numeric',
            'maxUse' => 'required|numeric',
            // 'event' => 'required|string|exists'  // Menambahkan validasi untuk event
        ]);
        $validate->validate();

        // Cari voucher yang akan diupdate
        $voucher = $this->ownedVoucherByIdQuery($request->id)->firstOrFail();
        $targetEvent = $this->ownedEventQuery($request->event)->firstOrFail();

        // Tentukan nominal berdasarkan unit (rupiah atau persen)
        if ($request->unit === 'rupiah') {
            $nominal = $request->nominalRupiah;
        } else {
            $nominal = $request->nominalPersen;
        }

        // Update data voucher
        $voucher->code = $request->code;
        $voucher->unit = $request->unit;
        $voucher->nominal = $nominal;
        $voucher->min_beli = $request->min;
        $voucher->max_disc = $request->max;
        $voucher->limit = $request->maxUse;
        $voucher->event_uid = $targetEvent->uid; // Update event_uid (mengaitkan voucher dengan event baru)

        // Simpan perubahan
        $voucher->save();

        // Redirect dengan pesan berhasil
        return redirect()->back()->with('voucher', 'Voucher berhasil diperbarui');
    }

    private function ownedEventQuery(string $uid)
    {
        return Event::where('uid', $uid)->where('user_uid', Auth::user()->uid);
    }

    private function ownedTalentQuery(string $id)
    {
        return Talent::query()
            ->where(function ($query) use ($id) {
                $query->where('id', $id)->orWhere('uid', $id);
            })
            ->whereHas('event', fn ($query) => $query->where('user_uid', Auth::user()->uid));
    }

    private function ownedHargaQuery(string $id)
    {
        return Harga::query()
            ->where('id', $id)
            ->whereHas('event', fn ($query) => $query->where('user_uid', Auth::user()->uid));
    }

    private function ownedPartnerQuery(string $uid)
    {
        return Partner::query()
            ->where('uid', $uid)
            ->where(function ($query) {
                $query->where('user_uid', Auth::user()->uid)
                    ->orWhereHas('event', fn ($event) => $event->where('user_uid', Auth::user()->uid));
            });
    }

    private function ownedVoucherByIdQuery(string $id)
    {
        return Voucher::query()
            ->where('id', $id)
            ->where(function ($query) {
                $query->where('user_uid', Auth::user()->uid)
                    ->orWhereHas('event', fn ($event) => $event->where('user_uid', Auth::user()->uid));
            });
    }

    private function minimumLockedQty(Harga $harga): int
    {
        $cartQuantity = (int) $harga->hargaCarts()
            ->whereHas('cart', fn ($query) => $query->whereIn('status', self::LOCKED_QTY_STATUSES))
            ->sum('quantity');

        return max((int) $harga->sold_qty + (int) $harga->reserved_qty, $cartQuantity);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed|regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
        ], [
            'new_password.regex' => 'Password harus mengandung huruf dan angka.',
        ]);

        $user = User::where('uid', Auth::user()->uid)->firstOrFail();

        if (! Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors([
                'current_password' => 'Password saat ini yang Anda masukkan salah!',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->new_password),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()->delete();
        $request->session()->regenerate();

        return redirect()->back()->with('editProfile', 'Password berhasil diubah untuk alasan keamanan!');
    }
}
