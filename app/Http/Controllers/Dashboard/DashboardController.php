<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Cart;
use App\Models\Term;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Slider;
use App\Models\Talent;
use App\Models\Contact;
use App\Models\Landing;
use App\Models\Provinsi;
use App\Models\HargaCart;
use App\Models\Penarikan;
use Illuminate\View\View;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\BankIndonesia;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Cash;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = User::where('role', 'user')->count();
        $transaksi = Transaction::select(['amount'])->where('status_transaksi', 'SUCCESS')->get();

        $tra = 0;
        foreach ($transaksi as $key => $tr) {
            $tra += $transaksi[$key]->amount;
        }

        $totalTransaksi = Cart::where('status', 'SUCCESS')->get();
        $gr = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid',)
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->select(DB::raw('DATE(carts.created_at) as hargadate'), DB::raw('SUM(harga_carts.quantity) as total_qty'), DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_amount'))
            ->groupBy(DB::raw('DATE(carts.created_at)'))
            ->get();
        // dd($totalTransaksi);

        $date = [];
        $qty = [];
        $amount = [];
        foreach ($gr as $grs) {
            $date[] = $grs->hargadate;
        }
        foreach ($gr as $grs) {
            $qty[] = $grs->total_qty;
        }
        foreach ($gr as $grs) {
            $amount[] = $grs->total_amount;
        }


        $genderCounts = Cart::join('users', 'users.uid', '=', 'carts.user_uid')
            // ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->select('users.gender', DB::raw('COUNT(*) as count'))
            // ->where('events.user_uid', Auth::user()->uid)
            ->where('carts.status', 'SUCCESS')
            ->groupBy('users.gender')
            ->pluck('count', 'users.gender')
            ->toArray();
        $pria = $genderCounts['wanita'] ?? 0;
        $wanita = $genderCounts['pria'] ?? 0;




        // Hitung total pengguna
        $totalUsers = $wanita + $pria;

        // Hitung persentase
        $persentaseWanita = $totalUsers > 0 ? ($wanita / $totalUsers) * 100 : 0;
        $persentasePria = $totalUsers > 0 ? ($pria / $totalUsers) * 100 : 0;

        $dataUser = [$wanita, $pria, $persentaseWanita, $persentasePria];
        // dd($genderCounts);

        $birtday = Cart::join('users', 'users.uid', '=', 'carts.user_uid')
            // ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->select(
                DB::raw(
                    "
            CASE
                WHEN TIMESTAMPDIFF(YEAR, users.birthday, users.created_at) < 18 THEN '<18 thn'
                WHEN TIMESTAMPDIFF(YEAR, users.birthday, users.created_at) BETWEEN 18 AND 25 THEN '18s/d25 thn'
                ELSE '>25thn'
            END AS age_group"
                ),
                'users.gender',
                DB::raw('COUNT(*) as count')
            )
            ->where('carts.status', 'SUCCESS')
            ->groupBy('age_group', 'users.gender')
            ->get()
            ->groupBy('age_group')
            ->map(function ($group) {
                // Kelompokkan berdasarkan gender dan hitung jumlah
                return $group->groupBy('gender')->mapWithKeys(function ($item, $key) {
                    return [$key => $item->sum('count')];
                })->sortByDesc(function ($value, $key) {
                    // Custom sorting: menempatkan pria di awal
                    return $key === 'pria' ? 0 : 1;
                });
            })
            ->toArray();
        // dd($birtday);
        return view('backend.content.dashboard', [
            'title' => 'Admin',
            'countUser' => $user,
            'transaction' => $tra,
            'totalTransaksi' => $totalTransaksi,
            'date' => $date,
            'qty' => $qty,
            'amount' => $amount,
            'dataUser' => $dataUser,
            'birtday' => $birtday
        ]);
    }
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

    public function landing(Request $request)
    {
        $slide = Slider::orderBy('sort', 'asc')->get();
        $logo = Landing::all();

        // dd($logo[0]->logo);
        return view('backend.content.landing', [
            'title' => 'Landing',
            'slide' => $slide,
            'slider' => $slide,
            'logo' => $logo,

        ]);
    }

    public function seo(Request $request)
    {
        $logo = Landing::all();
        $contact = Contact::all();
        return view('backend.content.seo', [
            'title' => 'Seo',
            'logo' => $logo,
            'contact' => $contact

        ]);
    }

    public function term()
    {
        $term = Term::all();
        return view('backend.content.term', [
            'title' => 'Term And Condition',
            'term' => $term,
        ]);
    }





    public function user($data = null)
    {
        // $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');

        // dd($provinsi);
        // if ($http->successful()) {
        //     $provinsi = $http->json();
        // }
        $provinsi = Provinsi::all();
        $datas = [];
        if ($data === 'admin') {
            foreach ($provinsi as $provinsis) {
                $datas[] = $provinsis['name'];
            }
            // dd($datas[0]);
            $admin = User::where('role', 'admin')->get();
            return view('backend.content.user.admin', ['title' => 'Role Admin', 'users' => $admin, 'provinsi' => $provinsi, 'datas' => $datas]);
        }

        if ($data === 'penyewa') {
            $penyewa = User::where('role', 'penyewa')->get();
            return view('backend.content.user.penyewa', ['title' => 'Role Penyewa', 'users' => $penyewa, 'provinsi' => $provinsi]);
        }
        if ($data === 'cashes') {
            $cashes = Cash::all();
            return view('backend.content.user.cashes', ['title' => 'Role Cashes', 'users' => $cashes, 'provinsi' => $provinsi]);
        }

        if ($data === null) {
            $users = User::where('role', 'user')->get();
            return view('backend.content.user.user', ['title' => 'Role User', 'users' => $users, 'provinsi' => $provinsi]);
        }
    }

    public function penarikan()
    {
        $penarikan = Penarikan::join('users', 'users.uid', '=', 'penarikans.uid_user')
            ->select(
                'penarikans.uid',
                'penarikans.uid_user',
                'penarikans.amount',
                'penarikans.kwitansi',
                'penarikans.status',
                'penarikans.created_at',
                'penarikans.updated_at',
                'users.name',
                'users.email',
                'users.gambar'
            )
            ->get();
        $t = 0;
        $pending = 0;
        $success = 0;
        foreach ($penarikan as $p) {
            $t += $p->amount;
            if ($p->status === 'PENDING') {
                $pending += $p->amount;
            } else {
                $success += $p->amount;
            }
        }
        //    dd($penarikan);
        return view('backend.content.penarikan', [
            'title' => 'Penarikan',
            'penarikan' => $penarikan,
            'totalPenarikan' => $t,
            'pending' => $pending,
            'success' => $success,
            'count' => count($penarikan),
        ]);
    }


    public function profile()
    {
        error_reporting(0);

        $data = User::where('users.uid', Auth::user()->uid)
            ->join('banks', 'banks.uid', '=', 'users.uid')
            ->first();
        // dd($data);
        // $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
        // if ($http->successful()) {
        //     $provinsi = $http->json();
        // }
        $provinsi = Provinsi::all();
        // dd($provinsi);
        $bi = BankIndonesia::all();

        return view(
            'backend.content.profile',
            [
                'title' => 'profile',
                'profile' => $data,
                'bi' => $bi,
                'pr' => $provinsi
            ]
        );
    }
}
