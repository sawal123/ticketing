<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\Harga;
use App\Models\Penarikan;
use App\Models\Talent;
use App\Models\Voucher;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\HargaCart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Type\Time;

class AddController extends Controller
{
    public function addEvent(Request $request): RedirectResponse
    {
        $validate = Validator::make($request->all(), [
            'event' => 'required|string|max:255',
            // 'fee' => 'required|numeric',
            'alamat' => 'required|string|max:255',
            'start' => 'required|string',
            'end' => 'required|string',
            'map' => 'required|string|max:255',
            'deskripsi' => 'required|string',

        ]);
        $validate->validate();

        $uid = Str::random();
        // dd($uid);

        $startEvent = new EventDate([
            'uid' => $uid,
            'start' => $request->start,
            'end' => $request->end
        ]);

        $event = new Event([
            'uid' => $uid,
            'user_uid' => Auth::user()->uid,
            'event' => $request->event,
            'alamat' => $request->alamat,
            'tanggal' =>  $request->start,
            'status' => 'active',
            'fee' => 10000,
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

        try {
            DB::beginTransaction();
            $event->save();
            $startEvent->save();
            DB::commit();
            return redirect('dashboard/event/eventDetail/' . $uid)->with('addEvent', 'Event Berhasil Disimpan..');
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
        $event = Harga::where('kategori', $request->kategori)->where('uid', $request->uid)->first();
        // dd($event);
        if ($event === null) {
            $harga = new Harga([
                'uid' => $request->uid,
                'kategori' => $request->kategori,
                'qty' => $request->qty,
                'harga' => $request->harga,
            ]);
            try {
                $harga->save();
                return redirect()->back()->with('harga', 'Harga berhasil disimpan');
            } catch (Exception $e) {
                return redirect()->back()->with('deleteHarga', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('deleteHarga', 'Nama Ticket Tidak Boleh Sama!');
        }
    }

    public function addVoucher(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'code' => 'string|required|max:50',
            'unit' => 'string|required|max:255',
            'nominal' => 'numeric|required',
            'min' => 'required|numeric',
            'max' => 'required|numeric',
            'maxUse' => 'required|numeric'
        ]);
        $validate->validate();

        $uid = Str::random('10');

        $voucher = new Voucher([
            'uid' => $uid,
            'user_uid' => Auth::user()->uid,
            'event_uid' => 'null',
            'code' => $request->code,
            'unit' => $request->unit,
            'nominal' => $request->nominal,
            'min_beli' => $request->min,
            'max_disc' => $request->max,
            'digunakan' => 0,
            'limit' => $request->maxUse,
            'status' => 'active'
        ]);
        $voucher->save();
        return redirect()->back()->with('voucher', 'Voucher berhasil disimpan');
    }

    public function addPenarikan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'amount' => 'required|numeric'
        ]);
        $validate->validate();
        $amount = (int)$request->amount;

        $totalHargaCart = Cart::select(['harga_carts.harga_ticket', 'harga_carts.quantity'])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        $totalSaldo = 0;

        $success = Penarikan::where('status', 'SUCCESS')
            ->where('uid_user', Auth::user()->uid)
            ->get();
        $paid = 0;
        foreach ($success as $key => $paids) {
            $paid += (int) $success[$key]->amount;
        }

        foreach ($totalHargaCart as $key => $tHC) {
            $totalSaldo += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
        }
        $totali = 0;
        $totali = $totalSaldo - $paid;
        if ($totali < 1) {
            return redirect()->back()->with('error', 'Saldo Anda tidak mencukupi!');
        }
        // dd($totali);
        if ($totali < $amount) {
            return redirect()->back()->with('error', 'Saldo Anda tidak mencukupi!');
        } else {
            $uid = Str::random('10');
            $penarikan = new Penarikan([
                'uid' => $uid,
                'uid_user' => Auth::user()->uid,
                'amount' => $request->amount,
                'note' => 'Penarikan',
                'kwitansi' => $totalSaldo,
                'status' => 'PENDING'
            ]);
        }

        // dd($penarikan);
        try {
            $penarikan->save();
            return redirect()->back()->with('penarikan', 'Penarikan berhasil diajukan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Pengajuan Gagal!');
        }
    }

    public function addCash(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'uid' => 'string|required|max:50',
            'event' => 'string|required|max:255',
            'ticket' => 'string|required',
            'qty' => 'numeric|required',
            'name' => 'required|string',
            'alamat' => 'required|string',
            'ttl' => 'required|string',
            'total' => 'required|numeric'
        ]);
        $validate->validate();
        $number = mt_rand(1000, 9999999999);
        $invoice = str_pad($number, 10, '0', STR_PAD_LEFT);
        $str = Str::random('10');

        $uid =  $request->uid;
        $event =  $request->event;
        $ticket =  $request->ticket;
        $qty =  $request->qty;
        $nama =  $request->name;
        $alamat =  $request->alamat;
        $ttl =  $request->ttl;
        $total =  $request->total;


        $events = Event::where('event', $event)->first();
        $kategoriTicket = Harga::where('uid', $events->uid)->where('kategori', $ticket)->first();

        $cart = new Cart([
            'uid' => $str,
            'user_uid' => $uid,
            'event_uid' => $events->uid,
            'invoice' => 'INV-' . $invoice,
            'status' => 'SUCCESS',
            'konfirmasi' => '1',
            'link' => null,
            'payment_type' => 'cash',
        ]);

        $hargaCart = new HargaCart([
            'orderBy' => '1',
            'uid' => $str,
            'event_uid' => $events->uid,
            'quantity' => $qty,
            'harga_ticket' => $kategoriTicket->harga,
            'kategori_harga' => $kategoriTicket->kategori ,
        ]);
        // dd($hargaCart);
        $cash = new Cash([
            'uid' => $str,
            'uid_user' => $uid,
            'uid_event' => $events->uid,
            'name' => $nama,
            'alamat' => $alamat,
            'lahir' => $ttl,
        ]);

        try {
            $cart->save();
            $hargaCart->save();
            $cash->save();
            return redirect()->back()->with('success', 'Pembelian Cash Berhasil');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
