<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Event;
use App\Models\Harga;
use App\Models\Talent;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class EditController extends Controller
{
    public function editEvent(Request $request)
    {
        $event = Event::where('uid', $request->uid)->where('user_uid', Auth::user()->uid)->first(); // Mengambil instance model yang akan diupdate

        $tanggal = date('Y-m-d H:i', strtotime($request->tanggal));
        $event->event = $request->event;
        $event->alamat = $request->alamat;
        $event->tanggal = $tanggal;
        $event->status = $request->status;
        $event->fee = $request->fee;
        $event->deskripsi = $request->deskripsi;
        $event->map = $request->map;
        $event->slug= Str::slug($request->event);

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $fileName = $event->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/cover/', $fileName);
            $event->cover = $fileName;
        }

        $event->save();
        return redirect('/dashboard/event/eventDetail/' . $request->uid)->with('success', 'Berhasil di Update');
    }

    public function editTalent(Request $request)
    {
        $uid = $request->uid;
        $talent = $request->talent;

        $talents = Talent::where('uid', $uid)->first();
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
}
