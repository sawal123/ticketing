<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Harga;
use App\Models\Talent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Slider;

class editController extends Controller
{
    //

    public function editEvent(Request $request)
    {
        $event = Event::where('uid', $request->uid)->first(); // Mengambil instance model yang akan diupdate

        $tanggal = date('Y-m-d H:i', strtotime($request->tanggal));
        $event->event = $request->event;
        $event->alamat = $request->alamat;
        $event->tanggal = $tanggal;
        $event->status = $request->status;
        $event->deskripsi = $request->deskripsi;
        $event->map = $request->map;

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $fileName = $event->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/cover/', $fileName);
            $event->cover = $fileName;
        }

        $event->save();
        return redirect('/event/eventDetail?id=' . $request->uid)->with('success', 'Berhasil di Update');
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
        return redirect()->back();
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

    public function editSlide(Request $request)
    {
        $slide = Slider::where('uid', $request->uid)->first();
        $slide->uid = $request->uid;
        $slide->title = $request->title;
        $slide->url = $request->url;
        $slide->sort = $request->sort;

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $fileName = $slide->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/slide/', $fileName);
            $slide->gambar = $fileName;
        }


        $slide->save();
        return redirect()->back()->with('editSlide', 'Slide Berhasil Diubah');
    }
}
