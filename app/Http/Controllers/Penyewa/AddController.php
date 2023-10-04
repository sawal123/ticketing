<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Event;
use App\Models\Harga;
use App\Models\Talent;
use App\Models\Voucher;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class AddController extends Controller
{
    public function addEvent(Request $request): RedirectResponse
    {
        $validate = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            'fee' => 'required|numeric',
            'alamat' => 'required|string|max:255',
            'tanggal' => 'required|string',
            'map' => 'required|string|max:255',
            'deskripsi' => 'string|max:255',

        ]);
        $validate->validate();

        $uid = Str::random();
        // dd($uid);

        $event = new Event([
            'uid' => $uid,
            'user_uid' => Auth::user()->uid,
            'event' => $request->event,
            'alamat' => $request->alamat,
            'tanggal' =>  $request->tanggal,
            'status' => 'active',
            'fee' => $request->fee,
            'deskripsi' => $request->deskripsi,
            'map' => $request->map,
            'slug' => Str::slug($request->event),
            'konfirmasi' => null
        ]);
        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $fileName = $event['uid_outlet'] . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/cover/', $fileName); // Simpan di direktori 'public/outlet/'
            $event['cover'] = $fileName; // Simpan nama file gambar di kolom 'gambar' pada tabel
        }
        // dd($event);

        try {
            DB::beginTransaction();
            $event->save();
            DB::commit();
            return redirect('dashboard/event/eventDetail/' . $uid)->with('addEvent', 'Event Berhasil Disimpan..');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Tambah Event Gagal. Silahkan coba lagi.');
        }
    }

    public function addTalent(Request $request)
    {

        $talent = new Talent([
            'uid' => $request->uid,
            'talent' => $request->talent,
        ]);
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $fileName = $talent['uid_outlet'] . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/talent/', $fileName); // Simpan di direktori 'public/outlet/'
            $talent['gambar'] = $fileName; // Simpan nama file gambar di kolom 'gambar' pada tabel
        }
        $talent->save();
        return redirect()->back()->with('talent', 'Talent Berhasil disimpan');
    }
    public function addHarga(Request $request)
    {
        // dd($request->qty);
        $harga = new Harga([
            'uid' => $request->uid,
            'kategori' => $request->kategori,
            'qty' => $request->qty,
            'harga' => $request->harga,
        ]);
        $harga->save();
        return redirect()->back()->with('harga', 'Harga berhasil disimpan');
    }

    public function addVoucher(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'code' => 'string|required|max:50',
            'unit' => 'string|required|max:255',
            'nominal' => 'numeric|required',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
            'maxUse' => 'required|numeric'
        ]);
        $validate->validate();

        $uid = Str::random('10');

        $voucher = new Voucher([
            'uid' => $uid,
            'user_uid' => Auth::user()->uid,
            'event_uid' => 'null',
            'code' => $request->code,
            'unit' => $request->unit,
            'nominal' => $request->nominal,
            'min_beli' => $request->min,
            'max_disc' => $request->max,
            'digunakan' => 0,
            'limit' => $request->maxUse,
            'status' => 'active'
        ]);
        $voucher->save();
        return redirect()->back()->with('voucher', 'Voucher berhasil disimpan');
    }
}
