<?php

namespace App\Livewire\Admin;

use App\Models\Cart;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardDemo extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        // 1. Core Metrics
        $totalUsers = User::where('role', 'user')->count();
        $totalOmset = Cart::where('status', 'SUCCESS')
            ->join('harga_carts', 'carts.uid', '=', 'harga_carts.uid')
            ->sum(DB::raw('harga_carts.quantity * harga_carts.harga_ticket'));
        $totalTransactions = Cart::where('status', 'SUCCESS')->count();

        // 2. Demographic Data (Gender & Age) using Eloquent Relations
        $successfulCarts = Cart::with('users')
            ->where('status', 'SUCCESS')
            ->get();

        $genderData = ['pria' => 0, 'wanita' => 0];
        $ageData = ['<18' => 0, '18-25' => 0, '>25' => 0];

        foreach ($successfulCarts as $cart) {
            if ($cart->users) {
                // Gender
                $gender = strtolower($cart->users->gender);
                if (isset($genderData[$gender])) {
                    $genderData[$gender]++;
                } elseif ($gender == 'laki-laki' || $gender == 'pria') {
                    $genderData['pria']++;
                } elseif ($gender == 'perempuan' || $gender == 'wanita') {
                    $genderData['wanita']++;
                }

                // Age
                if ($cart->users->birthday) {
                    $age = Carbon::parse($cart->users->birthday)->age;
                    if ($age < 18) {
                        $ageData['<18']++;
                    } elseif ($age <= 25) {
                        $ageData['18-25']++;
                    } else {
                        $ageData['>25']++;
                    }
                }
            }
        }

        // 3. Trend Data for Sparklines (Last 7 days)
        $last7Days = collect(range(6, 0))->map(function ($days) {
            return Carbon::now()->subDays($days)->format('Y-m-d');
        });

        $dailyRevenue = Cart::where('carts.status', 'SUCCESS')
            ->where('carts.created_at', '>=', Carbon::now()->subDays(7))
            ->join('harga_carts', 'carts.uid', '=', 'harga_carts.uid')
            ->select(
                DB::raw('DATE(carts.created_at) as date'),
                DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as revenue')
            )
            ->groupBy('date')
            ->get()
            ->pluck('revenue', 'date');

        $trendData = $last7Days->map(fn ($date) => $dailyRevenue->get($date, 0))->toArray();

        // 4. Recent Transactions for Table
        $recentTransactions = Transaction::with(['user', 'event'])
            ->where('status_transaksi', 'SUCCESS')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('livewire.admin.dashboard-demo', [
            'totalUsers' => $totalUsers,
            'totalOmset' => $totalOmset,
            'totalTransactions' => $totalTransactions,
            'genderData' => $genderData,
            'ageData' => $ageData,
            'trendData' => $trendData,
            'recentTransactions' => $recentTransactions,
        ])->layout('admin.layout', ['title' => 'Dashboard Demo']);
    }
}
