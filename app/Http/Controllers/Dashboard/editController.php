<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Bank;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Term;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Slider;
use App\Models\Talent;
use App\Models\Contact;
use App\Models\Landing;
use App\Models\Penarikan;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Jobs\sendEmailTrnsaksi;
use App\Mail\CashNotifikasiMail;
use App\Jobs\sendEmailETransaksi;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Validated;
use App\Mail\MidtransPaymentNotification;
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

    public function editTalent(Request $request)
    {
        $uid = $request->uid;
        $talent = $request->talent;

        $talents = Talent::where('id', $uid)->first();
        $talents->talent = $talent;

        if ($request->hasFile('gambar')) {
            $imagePath = public_path() . '/storage/talent/' . $talents->gambar;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }
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
            $imagePath = public_path() . '/storage/slide/' . $slide->gambar;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }
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
            $imagePath = public_path() . '/storage/user/' . $user->gambar;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }
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

    public function editDeskripis(Request $request)
    {
        $id = $request->id;
        $des = $request->description;
        $deskripis = Landing::where('id', $id)->first();

        $deskripis->description = $des;
        $deskripis->save();
        return redirect()->back()->with('success', 'Deskripsi Meta Berhasil di Ubah');
    }
    public function editKeyword(Request $request)
    {
        $id = $request->id;
        $key = $request->keyword;
        $keyword = Landing::where('id', $id)->first();

        $keyword->keyword = $key;
        $keyword->save();
        return redirect()->back()->with('success', 'Keyword Meta Berhasil di Ubah');
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
            $imagePath = public_path() . '/storage/user/' . $user->gambar;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }
            $file = $request->file('poto');
            $fileName = $user->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/user/', $fileName);
            $user->gambar = $fileName;
        }

        if ($request->password !== null) {
            $user->password = bcrypt($request->password);
        }
        $user->save();
        if ($user->role === 'penyewa') {
            return redirect()->back()->with('editUser', 'Penyewa Berhasil Diubah');
        } else {
            return redirect()->back()->with('editUser', 'Admin Berhasil Diubah');
        }
        // dd($request->poto);

    }

    public function editCashes(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'uid' => 'required|string',
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email',
            'lahir' => 'date',
            'alamat' => 'required|string|max:255',
            'nomor' => 'required|numeric',
            'gender' => 'required|string|max:20'
        ]);
        $validate->validate();

        $cashes = Cash::where('uid', $request->uid)->first();
        $cashes->name = $request->nama;
        $cashes->email = $request->email;
        $cashes->lahir = $request->lahir;
        $cashes->alamat = $request->alamat;
        $cashes->nomor = $request->nomor;
        $cashes->gender = $request->gender;
        $cashes->save();
        return redirect()->back()->with('success', 'Cashes Berhasil Diubah');
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

    public function editTransaksi(Request $request)
    {
        $uid = $request->uid;
        $name = $request->name;
        $barcode = $request->inv;

        $user = User::where("name", $name)->first();
        $transaksis = Transaction::where("uid", $request->uid)->first();
        $carts = Cart::where("uid", $request->uid)->first();
        $cash = Cash::where('uid', $uid)->first();

        if ($request->status === "SUCCESS") {
            if ($carts->payment_type === 'cash') {
                // Mail::to($cash->email)->send(new CashNotifikasiMail($cash->name,  $barcode));
                $send = new sendEmailTrnsaksi($cash->email, $cash->name, $barcode);
               dispatch($send);
            } else {
                // Mail::to($user->email)->send(new MidtransPaymentNotification($user, $carts, $barcode));
                $send = new sendEmailETransaksi($user, $carts, $barcode);
                dispatch($send);
            }
        }

        $carts->status = $request->status;
        if ($transaksis) {
            $transaksis->status_transaksi = $request->status;
            $transaksis->save();
        }
        $carts->save();
        // $transaksis->save();
        return redirect()->back()->with("success", "Transaksi Berhasil di Ubah");
    }


    public function editPro(Request $request)
    {

        $validate = Validator::make($request->all(), [
            'nama' => 'required|string',
            'nomor' => 'required|numeric',
            'email' => 'required|email',
            'date' => 'required|string',
            'gender' => 'required|string',
            'provinsi' => 'required|string',
            'alamat' => 'required|string',
        ]);

        $validate->validate();
        $user = User::where('uid', Auth::user()->uid)->first();
        // dd($user);
        $user->name = $request->nama;
        $user->nomor = $request->nomor;
        $user->email = $request->email;
        $user->birthday = $request->date;
        $user->gender = $request->gender;
        $user->kota = $request->provinsi;
        $user->alamat = $request->alamat;

        if ($request->hasFile('img')) {
            $imagePath = public_path() . '/storage/user/' . $user->gambar;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }
            $file = $request->file('img');
            $fileName = $user->uid . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/user/', $fileName);
            $user->gambar = $fileName;
        }

        try {
            $user->save();
            return redirect()->back()->with('editProfile', 'Informasi Berhasil Di Update');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal Update. Silahkan coba lagi.');
        }
    }

    public function editRekening(Request $request)
    {

        $rek = Bank::where('uid', Auth::user()->uid)->first();
        if ($rek) {
            $rek->nama = $request->nama;
            $rek->bank = $request->bank;
            $rek->norek = $request->norek;
            // $rek->save();
        }
        try {
            $rek->save();
            return redirect()->back()->with('editRek', 'Rekening Berhasil Di Update');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal Update. Silahkan coba lagi.');
        }
    }
    public function editContact(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'sosmed' => 'string',
            'nama' => 'string',
            'link' => 'string|max:255|nullable',
        ]);
        $validate->validate();
        $con = Contact::where('id', $request->id)->first();
        $con->sosmed = $request->sosmed;
        $con->name = $request->nama;
        $con->link =  $request->link == null ? '' : $request->link;
        if ($request->hasFile('icon')) {
            $imagePath = public_path() . '/storage/sosmed/' . $con->icon;
            if (file_exists($imagePath) === true) {
                unlink($imagePath);
            }
            $file = $request->file('icon');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/sosmed/', $fileName);
            $con->icon = $fileName;
        }
        $con->save();
        return redirect()->back()->with('success', 'Contact Berhasil Di Ubah');
    }
}
