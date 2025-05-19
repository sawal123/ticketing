<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Bank;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Talent;
use App\Models\Partner;
use App\Models\Voucher;
use App\Models\EventDate;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Dotenv\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EditController extends Controller
{
    public function editEventPenyewa(Request $request)
    {
        $event = Event::where('uid', $request->uid)->where('user_uid', Auth::user()->uid)->first();
        $eventDate = EventDate::where('uid', $request->uid)->first();

        $tanggal = date('Y-m-d H:i', strtotime($request->tanggal));
        $event->event = $request->event;
        $event->alamat = $request->alamat;
        $event->tanggal = $tanggal;
        $eventDate->start = $request->start;
        $eventDate->end = $request->end;
        $event->status = $request->status;
        $event->deskripsi = $request->deskripsi;
        $event->map = $request->map;
        $event->slug = Str::slug($request->event);



        if ($request->hasFile('cover')) {
            $imagePath = public_path() . '/storage/cover/' . $event->cover;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }

            $file = $request->file('cover');
            $fileName = $event->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/cover/', $fileName);
            $event->cover = $fileName;
        }



        $eventDate->save();
        $event->save();
        return redirect('/dashboard/event/eventDetail/' . $request->uid)->with('success', 'Berhasil di Update');
    }

    public function editTalent(Request $request)
    {
        $uid = $request->uid;
        $talent = $request->talent;

        $talents = Talent::where('id', $uid)->first();
        $talents->talent = $talent;

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $fileName = $talents->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/talent/', $fileName);
            $talents->gambar = $fileName;
        }
        $talents->save();
        return redirect()->back()->with('success', 'Berhasil di Update');
    }

    public function editHarga(Request $request)
    {
        $id = $request->id;
        $harga = Harga::where('id', $id)->first();
        // dd($request->kategori);
        $harga->update([
            'kategori' => $request->kategori,
            'qty' => $request->qty,
            'harga' => $request->harga
        ]);

        return redirect()->back()->with('editHarga', 'Harga Berhasil Di Ubah');
    }

    public function editRekening(Request $request)
    {

        $rek = Bank::where('uid', Auth::user()->uid)->first();
        if ($rek) {
            $rek->nama = $request->nama;
            $rek->bank = $request->bank;
            $rek->norek = $request->norek;
            // $rek->save();
        }
        try {
            $rek->save();
            return redirect()->back()->with('editRek', 'Rekening Berhasil Di Update');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal Update. Silahkan coba lagi.');
        }
    }

    public function editProfile(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'nama' => 'required|string',
            'nomor' => 'required|numeric',
            'email' => 'required|email',
            'date' => 'required|string',
            'gender' => 'required|string',
            'provinsi' => 'required|string',
            'alamat' => 'required|string',
        ]);

        $validate->validate();
        $user = User::where('uid', Auth::user()->uid)->first();
        // dd($user);
        $user->name = $request->nama;
        $user->nomor = $request->nomor;
        $user->email = $request->email;
        $user->birthday = $request->date;
        $user->gender = $request->gender;
        $user->kota = $request->provinsi;
        $user->alamat = $request->alamat;

        if ($request->hasFile('img')) {
            $file = $request->file('img');
            $fileName = $user->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/user/', $fileName);
            $user->gambar = $fileName;
        }

        try {
            $user->save();
            return redirect()->back()->with('editProfile', 'Informasi Berhasil Di Update');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal Update. Silahkan coba lagi.');
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
        $partner = Partner::where('uid', $request->uid)->first();
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
        $voucher = Voucher::find($request->id);
        // dd($voucher->event_uid);

        // Pastikan voucher ditemukan
        if (!$voucher) {
            return redirect()->back()->with('vError', 'Voucher tidak ditemukan');
        }

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
        $voucher->event_uid = $request->event; // Update event_uid (mengaitkan voucher dengan event baru)

        // Simpan perubahan
        $voucher->save();

        // Redirect dengan pesan berhasil
        return redirect()->back()->with('voucher', 'Voucher berhasil diperbarui');
    }
}
