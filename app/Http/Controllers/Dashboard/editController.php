<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Harga;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Slider;
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
    public function profile()
    {
        $final = [];
        //  dd($data);
        $valueUser = [Auth::user()->name, Auth::user()->email, Auth::user()->nomor, Auth::user()->gambar];
        $dataUser = User::where('uid', Auth::user()->uid)->first();
        $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
       if($http->successful()){
        $provinsi = $http->json();
        // dd(compact('provinsi'));
        $com = compact('provinsi');
       }
    //    foreach($provinsi as $pro){
    //     $final[] = $provinsi;
    //    }
    //    dd($provinsi);

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
            'birthday'=> 'required|string|max:255',
            'kota'=> 'required|string|max:255'
        ]);
        $validate->validate();
        $user = User::where('uid', Auth::user()->uid)->first();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->nomor = $request->input('nomor');
        $user->gender = $request->input('gender');
        $user->birthday = $request->input('birthday');
        $user->kota = $request->input('kota');
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
}
