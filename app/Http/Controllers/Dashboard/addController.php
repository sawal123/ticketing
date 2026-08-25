<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Slider;
use App\Models\Talent;
use App\Models\Term;
use App\Models\User;
use App\Services\SecureImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class addController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

    public function addEvent(Request $request): RedirectResponse
    {
        return redirect()
            ->route('admin.event')
            ->with('error', 'Form event legacy admin sudah ditutup. Event baru harus diajukan oleh penyewa melalui form event baru.');
    }

    public function addTalent(Request $request)
    {
        $request->validate([
            'gambar' => SecureImageStorage::rules(),
        ]);

        $talent = new Talent([
            'uid' => $request->uid,
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
        $request->validate([
            'gambar' => SecureImageStorage::rules(),
        ]);

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
            $slider['gambar'] = $this->images->storeBasename($request->file('gambar'), 'slide');
        }
        $slider->save();

        return redirect()->back()->with('addSlide', 'Slide Berhasil Ditambah..');
    }

    public function addTerm(Request $request)
    {
        $uid = Str::uuid();
        $title = $request->title;
        $des = $request->term;
        $term = new Term;
        $term->uid = $uid;
        $term->title = $title;
        $term->term = $des;
        $term->save();

        return redirect()->back()->with('addTerm', 'Syarat dan Ketentuan Berhasil Ditambah..');
    }

    public function addAdmin(Request $request)
    {
        $mail = User::where('email', $request->email)->first();
        if ($mail) {
            return redirect()->back()->with('gagal', 'Email sudah terdaftar');
        }
        $validate = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email',
            'tanggal' => 'date',
            'kota' => 'string|max:50',
            'alamat' => 'required|string|max:255',
            'nomor' => 'required|numeric',
            'gender' => 'required|string|max:20',
            'gambar' => SecureImageStorage::rules(),
        ]);
        $validate->validate();
        $uid = Str::uuid();
        if ($request->role === 'penyewa') {
            $bank = new Bank([
                'uid' => $uid,
                'uid_user' => '',
                'nama' => '',
                'bank' => '',
                'norek' => '',
            ]);
            $bank->save();
        }

        $user = new User;
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
            $user->gambar = $this->images->storeBasename($request->file('gambar'), 'user');
        }

        if ($request->password !== null) {
            $user->password = bcrypt($request->password);
        }
        $user->save();

        if ($request->role === 'penyewa') {
            return redirect()->back()->with('addUser', 'Penyewa Berhasil Di Tambah');
        } else {
            return redirect()->back()->with('addUser', 'Admin Berhasil Di Tambah');
        }

        // dd($request->poto);

    }

    public function addContact(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'sosmed' => 'string',
            'nama' => 'string',
            'link' => 'string|max:255|nullable',
            'icon' => SecureImageStorage::rules(),
        ]);
        $validate->validate();
        // dd($request->link);

        $contact = Contact::create([
            'sosmed' => $request->sosmed,
            'name' => $request->nama,
            'link' => $request->link == null ? '' : $request->link,
            'icon' => 'null',
        ]);
        if ($request->hasFile('icon')) {
            $contact->icon = $this->images->storeBasename($request->file('icon'), 'sosmed');
            $contact->save();
        }

        return redirect()->back()->with('success', 'Contact Berhasil Di Tambah');
    }
}
