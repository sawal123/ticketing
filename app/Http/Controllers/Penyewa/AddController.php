<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
}
