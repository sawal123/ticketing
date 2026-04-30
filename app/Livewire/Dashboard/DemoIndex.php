<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Cart;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\Harga;
use App\Models\Partner;
use App\Models\Cash;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoIndex extends Component
{
    #[Layout('layouts.unified')]
    public $isSellModalOpen = false;
    public $isGenderModalOpen = false;

    // FORM CASH PROPERTIES
    public $selectedEventId;
    public $selectedEvent;
    public $availableTickets = [];
    public $selectedTickets = []; // Array of ['id' => uid, 'name' => name, 'price' => price, 'qty' => 1]
    
    public $buyerName;
    public $buyerEmail;
    public $buyerBirthday;
    public $buyerGender;
    public $isPaidCash = true;
    public $isDirectEntry = false;
    public $partnerId;

    public function toggleSellModal()
    {
        $this->resetCashForm();
        $this->isSellModalOpen = !$this->isSellModalOpen;
    }

    public function resetCashForm()
    {
        $this->reset(['selectedEventId', 'selectedEvent', 'availableTickets', 'selectedTickets', 'buyerName', 'buyerEmail', 'buyerBirthday', 'buyerGender', 'partnerId']);
        $this->isPaidCash = true;
        $this->isDirectEntry = false;
    }

    public function selectEvent($uid)
    {
        $this->selectedEventId = $uid;
        $this->selectedEvent = Event::where('uid', $uid)->first();
        $this->availableTickets = Harga::where('uid', $uid)->get();
        $this->selectedTickets = [];
    }

    public function updatedSelectedEventId($value)
    {
        if ($value) {
            $this->selectEvent($value);
        }
    }

    public function addTicket($ticketId)
    {
        $ticket = $this->availableTickets->where('id', $ticketId)->first();
        if ($ticket) {
            // Check if already in selected
            $exists = collect($this->selectedTickets)->firstWhere('id', $ticketId);
            if (!$exists) {
                $this->selectedTickets[] = [
                    'id' => $ticket->id,
                    'name' => $ticket->kategori,
                    'price' => $ticket->harga,
                    'qty' => 1
                ];
            }
        }
    }

    public function removeTicket($index)
    {
        unset($this->selectedTickets[$index]);
        $this->selectedTickets = array_values($this->selectedTickets);
    }

    public function getSubtotalProperty()
    {
        return collect($this->selectedTickets)->sum(function($item) {
            return $item['price'] * $item['qty'];
        });
    }

    public function getTaxProperty()
    {
        $feePercent = $this->selectedEvent->fee ?? 0;
        return ($feePercent / 100) * $this->subtotal;
    }

    public function getTotalProperty()
    {
        return $this->subtotal + $this->tax;
    }

    public function checkout()
    {
        $this->validate([
            'selectedEventId' => 'required',
            'selectedTickets' => 'required|array|min:1',
            'buyerName' => 'required|string|max:255',
            'buyerEmail' => 'required|email',
            'buyerBirthday' => 'required|date',
            'buyerGender' => 'required|in:pria,wanita',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
            
            // 1. GENERATE INVOICE
            $invoice = 'CSH' . date('Ymd') . mt_rand(1000, 9999);

            // 2. CREATE CART
            $cart = Cart::create([
                'uid' => $invoice,
                'user_uid' => $user->uid, // Admin/Staff who sells
                'event_uid' => $this->selectedEventId,
                'status' => 'SUCCESS',
                'payment_type' => 'cash',
                'total_harga' => $this->total,
                'created_at' => now(),
            ]);

            // 3. CREATE HARGA CARTS & UPDATE STOCK
            foreach ($this->selectedTickets as $item) {
                HargaCart::create([
                    'uid' => $cart->uid,
                    'harga_uid' => $item['id'],
                    'quantity' => $item['qty'],
                    'harga_ticket' => $item['price'],
                ]);

                // Update Stock logic if needed
                $ticketModel = Harga::where('uid', $item['id'])->first();
                if ($ticketModel) {
                    $ticketModel->decrement('stock', $item['qty']);
                }
            }

            // 4. CREATE TRANSACTION RECORD
            Transaction::create([
                'uid' => $cart->uid,
                'user_uid' => $user->uid,
                'event_uid' => $this->selectedEventId,
                'amount' => $this->total,
                'status' => 'SUCCESS',
                'type' => 'income',
            ]);

            // 5. CREATE CASH RECORD (The actual buyer data)
            Cash::create([
                'uid' => $cart->uid,
                'event' => $this->selectedEvent->event,
                'name' => $this->buyerName,
                'email' => $this->buyerEmail,
                'ttl' => $this->buyerBirthday,
                'gender' => $this->buyerGender,
                'total' => $this->total,
                'qty' => collect($this->selectedTickets)->sum('qty'),
                'partner' => $this->partnerId,
            ]);

            DB::commit();
            
            $this->dispatch('close-modal', name: 'sell-modal');
            session()->flash('success', 'Transaksi Cash Berhasil!');
            return redirect()->route('dashboard.demo');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function toggleGenderModal()
    {
        $this->isGenderModalOpen = !$this->isGenderModalOpen;
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // Fetch Partners for the form
        $partners = \App\Models\Partner::query();
        if (!$isAdmin) {
            $partners->where('referensi', $ownerId)->where('status', 'active');
        } else {
            $partners->where('status', 'active');
        }
        $availablePartners = $partners->get();

        // RUMUS DASAR PERHITUNGAN
        $rumusDasar = "
            (
                (harga_carts.quantity * harga_carts.harga_ticket) - 
                COALESCE(
                    CASE 
                        WHEN vouchers.unit = '%' OR vouchers.unit = 'persen' 
                        THEN (harga_carts.quantity * harga_carts.harga_ticket) * (vouchers.nominal / 100)
                        ELSE vouchers.nominal 
                    END, 
                0)
            ) 
            * (1 + (COALESCE(events.fee, 0) / 100))
        ";

        $queryBase = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
            ->where('carts.status', 'SUCCESS');

        if (!$isAdmin) {
            $queryBase->where('events.user_uid', $ownerId);
        }

        // STATISTIK UTAMA
        $stats = (clone $queryBase)->select(
            DB::raw("SUM($rumusDasar) as total_omset"),
            DB::raw("SUM(harga_carts.quantity) as total_tiket")
        )->first();

        $totalTransaksiQuery = Cart::where('status', 'SUCCESS');
        if (!$isAdmin) {
            $totalTransaksiQuery->whereHas('event', function($q) use ($ownerId) {
                $q->where('user_uid', $ownerId);
            });
        }
        $totalTransaksi = $totalTransaksiQuery->count();

        $totalEvent = Event::query();
        if (!$isAdmin) $totalEvent->where('user_uid', $ownerId);
        $totalEventCount = (clone $totalEvent)->count();
        $eventAktifCount = (clone $totalEvent)->where('konfirmasi', '1')->count();

        $activeEvents = (clone $totalEvent)->where('konfirmasi', '1')->latest()->get();

        // GRAPHIC ANALYTIC (Last 14 Days) - Multi Metric
        $dailyData = (clone $queryBase)
            ->where('carts.created_at', '>=', now()->subDays(14))
            ->select(
                DB::raw('DATE(carts.created_at) as date'),
                DB::raw("SUM(CASE WHEN carts.payment_type = 'cash' THEN $rumusDasar ELSE 0 END) as cash_amount"),
                DB::raw("SUM(CASE WHEN carts.payment_type != 'cash' THEN $rumusDasar ELSE 0 END) as online_amount"),
                DB::raw("SUM(harga_carts.quantity) as total_qty")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartLabels = $dailyData->pluck('date')->map(fn($d) => Carbon::parse($d)->format('d M'))->toArray();
        $chartCash = $dailyData->pluck('cash_amount')->toArray();
        $chartOnline = $dailyData->pluck('online_amount')->toArray();
        $chartQty = $dailyData->pluck('total_qty')->toArray();

        // GENDER & AGE DEMOGRAPHICS
        $demographics = (clone $queryBase)
            ->join('users', 'users.uid', '=', 'carts.user_uid')
            ->select(
                'users.gender',
                DB::raw("TIMESTAMPDIFF(YEAR, users.birthday, CURDATE()) as age")
            )
            ->get();

        $genderStats = [
            'pria' => $demographics->where('gender', 'pria')->count(),
            'wanita' => $demographics->where('gender', 'wanita')->count(),
            'age_18_25' => $demographics->whereBetween('age', [18, 25])->count(),
            'age_gt_25' => $demographics->where('age', '>', 25)->count(),
            'age_lt_18' => $demographics->where('age', '<', 18)->count(),
        ];

        return view('livewire.dashboard.demo-index', [
            'title' => 'Dashboard Overview',
            'stats' => [
                'omset' => $stats->total_omset ?? 0,
                'tiket' => $stats->total_tiket ?? 0,
                'transaksi' => $totalTransaksi,
                'total_event' => $totalEventCount,
                'event_aktif' => $eventAktifCount,
            ],
            'activeEvents' => $activeEvents,
            'availablePartners' => $availablePartners,
            'chart' => [
                'labels' => $chartLabels,
                'cash' => $chartCash,
                'online' => $chartOnline,
                'qty' => $chartQty,
            ],
            'gender' => $genderStats
        ]);
    }
}
