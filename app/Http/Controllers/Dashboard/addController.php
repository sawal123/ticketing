<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Event;

use App\Models\Harga;
use App\Models\Slider;
use App\Models\Talent;
use App\Models\Term;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Http\RedirectResponse;


class addController extends Controller
{
    //

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

    public function addSlide(Request $request)
    {

        $slide = Slider::orderBy('sort', 'desc')->first();
        if ($slide === null) {
            $angka = 1;
        } else {
            $angka = $slide->sort + 1;
        }

        $slider = new Slider([
            'uid' => Str::uuid(),
            'sort' => $angka,
            'title' => $request->title,
            'url' => $request->url,
        ]);
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $fileName = $slider['uid_outlet'] . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/slide/', $fileName); // Simpan di direktori 'public/outlet/'
            $slider['gambar'] = $fileName; // Simpan nama file gambar di kolom 'gambar' pada tabel
        }
        $slider->save();
        return redirect()->back()->with('addSlide', 'Slide Berhasil Ditambah..');
    }

    public function addTerm(Request $request)
    {
        $uid = Str::uuid();
        $title = $request->title;
        $des = $request->term;
        $term = new Term();
        $term->uid = $uid;
        $term->title = $title;
        $term->term = $des;
        $term->save();
        return redirect()->back()->with('addTerm', 'Syarat dan Ketentuan Berhasil Ditambah..');
    }
    public function addAdmin(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email',
            'tanggal' => 'date',
            'kota' => 'string|max:50',
            'alamat' => 'required|string|max:255',
            'nomor' => 'required|numeric',
            'gender' => 'required|string|max:20'
        ]);
        $validate->validate();
        $uid = Str::uuid();
        if ($request->role === 'penyewa') {
            $bank = new Bank([
                'uid' =>  $uid,
                'uid_user' => '',
                'nama' => '',
                'bank' => '',
                'norek' => ''
            ]);
            $bank->save();
        }

        $user = new User();
        $user->uid = $uid;
        $user->name = $request->nama;
        $user->email = $request->email;
        $user->birthday = $request->tanggal;
        $user->kota = $request->kota;
        $user->alamat = $request->alamat;
        $user->nomor = $request->nomor;
        $user->gender = $request->gender;
        $user->role = $request->role;

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $fileName = $user->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/user/', $fileName);
            $user->gambar = $fileName;
        }

        if ($request->password !== null) {
            $user->password = bcrypt($request->password);
        }
        $user->save();

        // dd($request->poto);
        return redirect()->back()->with('addUser', 'User Berhasil Di Tambah');
    }

    public function addContact(Request $request){
        $validate = Validator::make($request->all(),[
            'sosmed' => 'string|required',
            'nama'=> 'string|required',
            'link'=> 'string|required|max:255',
        ]);
        $validate->validate();

        $contact = Contact::create([
            'sosmed'=> $request->sosmed,
            'name'=> $request->nama,
            'link'=> $request->link,
            'icon'=> 'null'
        ]);
        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/sosmed/', $fileName);
            $contact->icon = $fileName;
            $contact->save();
        }
        return redirect()->back()->with('success', 'Contact Berhasil Di Tambah');
    }
}
