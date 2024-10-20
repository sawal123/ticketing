<?php

namespace App\Http\Controllers\Dashboard\Event;

use App\Models\Event;
use App\Models\Harga;
use App\Models\Talent;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function event(Request $request, $addEvent = null, $uid = null)
    {
        error_reporting(0);
        $event = Event::all();
        if ($addEvent === null) {
            $pagination = Event::paginate(12);

            if ($request->has('query')) {
                // Lakukan pencarian berdasarkan nama atau atribut lainnya
                $searchQuery = $request->input('query');
                $event = Event::where('nama', 'LIKE', "%$searchQuery%")
                    // tambahkan kondisi pencarian berdasarkan atribut lain jika diperlukan
                    ->paginate(12);
            }

            return view('backend.content.event', [
                'title' => 'Event',
                'event' => $event,
                'paginate' => $pagination
            ]);
        } elseif ($addEvent === 'addEvent') {

            return view('backend.semiPage.addEvent', [
                'title' => 'Add Event',

            ]);
        } elseif ($addEvent === 'eventDetail') {
            // $id = $_GET['id'];
            $eventDetail = Event::where('uid', $uid)->first();
            $talent = Talent::where('uid', $uid)->get();
            $harga = Harga::where('uid', $uid)->get();
            $user = User::where('uid', $eventDetail->user_uid)->first();
            // dd($eventDetail);
            if ($eventDetail === null) {
                abort('403');
            }
            // dd($eventDetail);
            return view('backend.semiPage.eventDetail', [
                'title' => 'Event Detail',
                'eventDetail' => $eventDetail,
                'talent' => $talent,
                'harga' => $harga,
                'us' => $user
            ]);
        }
    }
    public function ubahEvents($uid)
    {
        $ubahEvent = Event::where('uid', $uid)->first();
        return view('backend.semiPage.addEvent', [
            'title' => 'Ubah Event',
            'ubahEvent' => $ubahEvent
        ]);
    }

    public function addEvent(Request $request): RedirectResponse
    {
        $validate = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            'fee' => 'required|numeric',
            'alamat' => 'required|string|max:255',
            'tanggal' => 'required|string',
            'map' => 'required|string|max:255',
            'deskripsi' => 'required|string|max:255',

        ]);
        $validate->validate();

        $uid = Str::uuid();
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
            'slug' => Str::slug($request->event)
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
            return redirect('admin/event/eventDetail/' . $uid)->with('addEvent', 'Event Berhasil Disimpan..');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Tambah Event Gagal. Silahkan coba lagi.');
        }
    }

    public function deleteEvent($uid)
    {
        $event = Event::where('uid', $uid)->first();
        if ($event) {
            $imagePath = public_path() . '/storage/cover/' . $event->cover;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }
            $event->delete();
            $talent = Talent::where('uid', $event->uid)->get();
            if ($talent) {
                foreach ($talent as $talentItem) {
                    $imagePath = public_path() . '/storage/cover/' . $talentItem->gambar;
                    if (file_exists($imagePath) === true) {
                        unlink($imagePath);
                    }
                    $talentItem->delete();
                }
            }
            $harga = Harga::where('uid', $event->uid)->get();
            if ($harga) {
                foreach ($harga as $hargaItem) {
                    $hargaItem->delete();
                }
            }
        }

        return redirect()->back()->with('deleteEvent', 'Event Berhasil Dihapus');
    }

    public function setujuiEvent($data)
    {
        $event = Event::where('uid', $data)->first();
        $event->konfirmasi = '1';
        $event->save();
        return redirect()->back()->with('konfirmasi', 'Event Berhasil di Setujui dan di publish');
    }

    public function editEvent(Request $request)
    {
        $event = Event::where('uid', $request->uid)->first(); // Mengambil instance model yang akan diupdate

        $tanggal = date('Y-m-d H:i', strtotime($request->tanggal));
        $event->event = $request->event;
        $event->alamat = $request->alamat;
        $event->tanggal = $tanggal;
        $event->status = $request->status;
        $event->fee = $request->fee;
        $event->deskripsi = $request->deskripsi;
        $event->map = $request->map;

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

        $event->save();
        return redirect('/admin/event/eventDetail/' . $request->uid)->with('success', 'Berhasil di Update');
    }
}
