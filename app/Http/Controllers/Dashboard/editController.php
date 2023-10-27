<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Harga;
use App\Models\Landing;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Penarikan;
use App\Models\Slider;
use App\Models\Term;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

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
        $event->fee = $request->fee;
        $event->deskripsi = $request->deskripsi;
        $event->map = $request->map;

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');
            $fileName = $event->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/cover/', $fileName);
            $event->cover = $fileName;
        }

        $event->save();
        return redirect('/admin/event/eventDetail/' . $request->uid)->with('success', 'Berhasil di Update');
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
    public function profile()
    {
        $final = [];
        //  dd($data);
        $valueUser = [Auth::user()->name, Auth::user()->email, Auth::user()->nomor, Auth::user()->gambar];
        $dataUser = User::where('uid', Auth::user()->uid)->first();
        $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
        if ($http->successful()) {
            $provinsi = $http->json();
            $com = compact('provinsi');
        }

        return view(
            'frontend.page.editProfile',
            [
                'title' => 'Edit Profile',
                'dataUser' => $dataUser,
                'provinsi' => $provinsi
            ]
        );
    }
    public function editProfile(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email',
            'nomor' => 'required|numeric',
            'gender' => 'required|string|max:10',
            'birthday' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
            'alamat' => 'required|string|max:255'
        ]);
        $validate->validate();
        $user = User::where('uid', Auth::user()->uid)->first();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->nomor = $request->input('nomor');
        $user->gender = $request->input('gender');
        $user->birthday = $request->input('birthday');
        $user->kota = $request->input('kota');
        $user->alamat = $request->input('alamat');
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $fileName = $user->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/user/', $fileName);
            $user->gambar = $fileName;
        }
        // dd($user->name);
        if ($request->password === null) {
            $user->save();
            return redirect()->back()->with('editProfile', 'Profile Berhasil Diubah');
        } else {
            $user->password = bcrypt($request->password);
            $user->save();
            return redirect()->back()->with('editProfile', 'Profile & Password Berhasil Diubah');
        }

        // dd($request->password);
    }

    public function editLogo(Request $request)
    {
        // dd($data);
        $logo = Landing::where('id', $request->id)->first();
        // dd($logo);
        if ($logo === null) {
            $save = new Landing();
            $save->description = '';
            $save->keyword = '';
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $fileName = $save->id . '_' . time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/logo/', $fileName);
                $save->logo = $fileName;
            }
            $save->save();
        } else {
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $fileName = $logo->id . '_' . time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/logo/', $fileName);
                $logo->logo = $fileName;
            }
            $logo->save();
        }
        return redirect()->back()->with('editLogo', 'Logo Berhasil Diubah');
    }

    public function editIcon(Request $request)
    {
        // dd($data);
        $logo = Landing::where('id', $request->id)->first();
        // dd($logo);
        if ($logo === null) {
            $save = new Landing();
            $save->description = '';
            $save->keyword = '';
            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $fileName = $save->id . '_' . time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/logo/', $fileName);
                $save->icon = $fileName;
            }
            $save->save();
        } else {
            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $fileName = $logo->id . '_' . time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/logo/', $fileName);
                $logo->icon = $fileName;
            }
            $logo->save();
        }
        return redirect()->back()->with('editLogo', 'Logo Berhasil Diubah');
    }

    public function editDeskripis(Request $request){
        $id = $request->id;
        $des = $request->description;
        $deskripis = Landing::where('id', $id)->first();

        $deskripis->description = $des;
        $deskripis->save();
        return redirect()->back()->with('success','Deskripsi Meta Berhasil di Ubah');
    }
    public function editKeyword(Request $request){
        $id = $request->id;
        $key = $request->keyword;
        $keyword = Landing::where('id', $id)->first();

        $keyword->keyword = $key;
        $keyword->save();
        return redirect()->back()->with('success','Keyword Meta Berhasil di Ubah');
    }


    public function editTerm(Request $request)
    {
        $term = Term::where('uid', $request->uid)->first();

        $term->title = $request->title;
        $term->term = $request->term;
        $term->save();

        return redirect()->back()->with('editTerm', 'Term Berhasil Diubah');
    }

    public function editUser(Request $request)
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

        $user = User::where('uid', $request->uid)->first();
        $user->name = $request->nama;
        $user->email = $request->email;
        $user->birthday = $request->tanggal;
        $user->kota = $request->kota;
        $user->alamat = $request->alamat;
        $user->nomor = $request->nomor;
        $user->gender = $request->gender;

        if ($request->hasFile('poto')) {
            $file = $request->file('poto');
            $fileName = $user->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/user/', $fileName);
            $user->gambar = $fileName;
        }

        if ($request->password !== null) {
            $user->password = bcrypt($request->password);
        }
        $user->save();
        // dd($request->poto);
        return redirect()->back()->with('editUser', 'User Berhasil Diubah');
    }


    public function setujuiEvent($data)
    {
        $event = Event::where('uid', $data)->first();
        $event->konfirmasi = '1';
        $event->save();
        return redirect()->back()->with('konfirmasi', 'Event Berhasil di Setujui dan di publish');
    }

    public function editStatusInvoice(Request $request)
    {
        $status = $request->uid;
        // dd($status);
        $penarikan = Penarikan::where('uid', $request->uid)->first();
        // dd($penarikan);
        $penarikan->status = "SUCCESS";
        $penarikan->save();
        return redirect()->back()->with("success", "Konfirmasi Berhasil");
    }
}
