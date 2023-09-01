<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Event;

use App\Models\Harga;
use App\Models\Talent;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Http\RedirectResponse;


class addController extends Controller
{
    //

    public function addEvent(Request $request): RedirectResponse
    {
        $uid = Str::random();
        // dd($uid);

        $event = new Event([
            'uid' => $uid,
            'user_uid' => Auth::user()->uid,
            'event' => $request->event,
            'alamat' => $request->alamat,
            'tanggal' =>  $request->tanggal,
            'status' => 'active',
            'deskripsi' => $request->deskripsi,
            'map' => $request->map,
            'slug' => Str::slug($request->event)
        ]);
        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $fileName = $event['uid_outlet'] . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/cover/', $fileName); // Simpan di direktori 'public/outlet/'
            $event['cover'] = $fileName; // Simpan nama file gambar di kolom 'gambar' pada tabel
        }

        // try {
            // DB::beginTransaction();
            $event->save();
            // $talent->save();
            // $harga->save();
            // DB::commit();
            return redirect()->back()->with('success', 'Berhasil disimpan');
        // } catch (\Exception $e) {
        //     DB::rollback();
        //     return redirect()->back()->with('error', 'Event Gagal. Silakan coba lagi.');
        // }
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
}
