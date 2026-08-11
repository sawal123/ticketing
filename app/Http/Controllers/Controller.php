<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bank;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Penarikan;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function notif()
    {
        return view('email.notif-email');
    }

    public function invoice($uid = null)
    {

        if ($uid === null) {
            return redirect()->back();
        }

        // 1. Cek apakah ini penarikan
        $penarikan = Penarikan::join('users', 'users.uid', '=', 'penarikans.uid_user')
            ->select(
                'penarikans.uid',
                'penarikans.uid_user',
                'penarikans.amount',
                'penarikans.kwitansi',
                'penarikans.status',
                'penarikans.bank_name',
                'penarikans.bank_account_name',
                'penarikans.bank_account_number',
                'penarikans.created_at',
                'penarikans.updated_at',
                'users.name',
                'users.email',
                'users.gambar'
            )
            ->where('penarikans.uid', $uid)
            ->first();

        if ($penarikan) {
            $viewer = Auth::user();
            if ($viewer->role !== 'admin' && $viewer->uid !== $penarikan->uid_user) {
                abort(403);
            }

            $fallbackBank = null;
            if (! filled($penarikan->bank_name)
                || ! filled($penarikan->bank_account_name)
                || ! filled($penarikan->bank_account_number)) {
                $fallbackBank = Bank::where(function ($q) use ($penarikan) {
                    $q->where('uid_user', $penarikan->uid_user)
                        ->orWhere('uid', $penarikan->uid_user);
                })->latest()->first();
            }

            $bank = (object) [
                'nama' => filled($penarikan->bank_account_name) ? $penarikan->bank_account_name : ($fallbackBank->nama ?? '-'),
                'bank' => filled($penarikan->bank_name) ? $penarikan->bank_name : ($fallbackBank->bank ?? '-'),
                'norek' => filled($penarikan->bank_account_number) ? $penarikan->bank_account_number : ($fallbackBank->norek ?? '-'),
            ];
            $cekBank = Bank::all();
            $user = User::all();
            $sbank = [];
            foreach ($user as $value) {
                if ($value->role === 'admin') {
                    foreach ($cekBank as $value2) {
                        if ($value->uid === $value2->uid) {
                            $sbank[] = Bank::where('uid', $value2->uid)->first();
                        }
                    }
                }
            }

            return view('invoice', [
                'title' => 'Invoice Penarikan',
                'type' => 'penarikan',
                'penarikan' => $penarikan,
                'bankPenyewa' => $bank,
                'bankPengirim' => $sbank,
            ]);
        }

        // 2. Jika bukan penarikan, cek apakah ini transaksi (Cart)
        $cart = Cart::with(['users', 'event', 'hargaCarts.masterHarga'])->where('uid', $uid)->first();

        if ($cart) {
            if (! $this->userCanViewCart($cart)) {
                abort(403);
            }

            ActivityLog::safeCreate([
                'user_uid' => auth()->check() ? auth()->user()->uid : null,
                'activity' => 'Data Export / View',
                'login_status' => 'Success',
                'description' => 'Accessed invoice/ticket: '.($cart->invoice ?? $uid),
                'impact_level' => 'Sensitif',
                'ip_address' => request()->ip(),
                'location' => $this->getLocation(request()->ip()),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId(),
            ]);

            return view('invoice', [
                'title' => 'Invoice Transaksi',
                'type' => 'transaksi',
                'cart' => $cart,
            ]);
        }

        return redirect()->back();
    }

    private function userCanViewCart(Cart $cart): bool
    {
        $user = Auth::user();

        if ($user->role === 'admin' || $user->uid === $cart->user_uid) {
            return true;
        }

        if ($cart->payment_type === 'cash'
            && Cash::where('uid', $cart->uid)->where('email', $user->email)->exists()) {
            return true;
        }

        if (in_array($user->role, ['penyewa', 'staff'], true)) {
            $ownerUid = $user->role === 'staff' ? $user->parent_uid : $user->uid;

            return $cart->event && $cart->event->user_uid === $ownerUid;
        }

        return false;
    }

    protected function getLocation($ip)
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }
        try {
            $response = Http::get("http://ip-api.com/json/{$ip}?fields=city,country");
            if ($response->successful()) {
                $data = $response->json();

                return ($data['city'] ?? 'Unknown').', '.($data['country'] ?? 'Unknown');
            }
        } catch (\Exception $e) {
        }

        return 'Unknown';
    }
}
