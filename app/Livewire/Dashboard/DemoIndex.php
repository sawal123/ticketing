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
        $user = auth()->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $this->selectedEventId = $uid;
        $this->selectedEvent = Event::where('uid', $uid)
            ->where('konfirmasi', '1')
            ->where('status', 'active')
            ->when($user->role !== 'admin', fn ($query) => $query->where('user_uid', $ownerId))
            ->firstOrFail();
        $this->loadAvailableTickets();
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
        $ticket = Harga::where('id', $ticketId)
            ->where('uid', $this->selectedEventId)
            ->where('status', 'active')
            ->first();

        if (! $ticket || $this->getRemainingStock($ticket) < 1) {
            $this->addError('selectedTickets', 'Tiket tidak aktif atau sudah sold out.');
            $this->loadAvailableTickets();
            return;
        }

        $this->resetErrorBag('selectedTickets');

        $exists = collect($this->selectedTickets)->firstWhere('id', $ticket->id);
        if (! $exists) {
            $this->selectedTickets[] = [
                'id' => $ticket->id,
                'name' => $ticket->kategori,
                'price' => $ticket->harga,
                'qty' => 1,
                'max_qty' => $this->getRemainingStock($ticket),
            ];
        }
    }

    public function increaseTicketQty($index)
    {
        if (! isset($this->selectedTickets[$index])) {
            return;
        }

        $maxQty = (int) ($this->selectedTickets[$index]['max_qty'] ?? 0);
        $this->selectedTickets[$index]['qty'] = min($maxQty, (int) $this->selectedTickets[$index]['qty'] + 1);
    }

    public function decreaseTicketQty($index)
    {
        if (! isset($this->selectedTickets[$index])) {
            return;
        }

        $this->selectedTickets[$index]['qty'] = max(1, (int) $this->selectedTickets[$index]['qty'] - 1);
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
            'selectedTickets.*.id' => 'required|integer|distinct',
            'selectedTickets.*.qty' => 'required|integer|min:1',
            'buyerName' => 'required|string|max:255',
            'buyerEmail' => 'required|email',
            'buyerBirthday' => 'required|date',
            'buyerGender' => 'required|in:pria,wanita',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

            $event = Event::where('uid', $this->selectedEventId)
                ->where('konfirmasi', '1')
                ->where('status', 'active')
                ->when($user->role !== 'admin', fn ($query) => $query->where('user_uid', $ownerId))
                ->lockForUpdate()
                ->firstOrFail();

            $validatedTickets = [];
            foreach ($this->selectedTickets as $item) {
                $ticket = Harga::where('id', $item['id'])
                    ->where('uid', $event->uid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $requestedQty = filter_var($item['qty'], FILTER_VALIDATE_INT);
                $remainingStock = $this->getRemainingStock($ticket);

                if ($ticket->status !== 'active' || $requestedQty === false || $requestedQty < 1 || $requestedQty > $remainingStock) {
                    throw new \RuntimeException("Tiket {$ticket->kategori} tidak aktif atau stok tidak mencukupi.");
                }

                $validatedTickets[] = [
                    'model' => $ticket,
                    'qty' => $requestedQty,
                ];
            }

            $subtotal = collect($validatedTickets)->sum(fn ($item) => $item['model']->harga * $item['qty']);
            $tax = (($event->fee ?? 0) / 100) * $subtotal;
            $total = $subtotal + $tax;
            
            // 1. GENERATE INVOICE
            $invoice = 'CSH' . date('Ymd') . mt_rand(1000, 9999);

            // 2. CREATE CART
            $cart = Cart::create([
                'uid' => $invoice,
                'user_uid' => $user->uid, // Admin/Staff who sells
                'event_uid' => $this->selectedEventId,
                'status' => 'SUCCESS',
                'payment_type' => 'cash',
                'total_harga' => $total,
                'created_at' => now(),
            ]);

            // 3. CREATE HARGA CARTS
            foreach ($validatedTickets as $item) {
                $ticket = $item['model'];

                HargaCart::create([
                    'uid' => $cart->uid,
                    'harga_id' => $ticket->id,
                    'event_uid' => $event->uid,
                    'quantity' => $item['qty'],
                    'harga_ticket' => $ticket->harga,
                    'kategori_harga' => $ticket->kategori,
                ]);
            }

            // 4. CREATE TRANSACTION RECORD
            Transaction::create([
                'uid' => $cart->uid,
                'user_uid' => $user->uid,
                'event_uid' => $this->selectedEventId,
                'amount' => $total,
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
                'total' => $total,
                'qty' => collect($validatedTickets)->sum('qty'),
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

    protected function loadAvailableTickets()
    {
        $this->availableTickets = Harga::where('uid', $this->selectedEventId)
            ->withSum(['hargaCarts as sold_count' => function ($query) {
                $query->whereHas('cart', fn ($cart) => $cart->where('status', 'SUCCESS'));
            }], 'quantity')
            ->get()
            ->each(function ($ticket) {
                $ticket->remaining_stock = max(0, (int) $ticket->qty - (int) ($ticket->sold_count ?? 0));
            });
    }

    protected function getRemainingStock(Harga $ticket)
    {
        $soldCount = $ticket->hargaCarts()
            ->whereHas('cart', fn ($cart) => $cart->where('status', 'SUCCESS'))
            ->sum('quantity');

        return max(0, (int) $ticket->qty - (int) $soldCount);
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
                        WHEN LOWER(vouchers.unit) = '%' OR LOWER(vouchers.unit) = 'persen' 
                        THEN 
                            CASE 
                                WHEN vouchers.max_disc > 0 AND ((harga_carts.quantity * harga_carts.harga_ticket) * (vouchers.nominal / 100)) > vouchers.max_disc
                                THEN vouchers.max_disc
                                ELSE (harga_carts.quantity * harga_carts.harga_ticket) * (vouchers.nominal / 100)
                            END
                        ELSE vouchers.nominal 
                    END, 
                0)
            ) 
            * (1 + (COALESCE(events.fee, 0) / 100))
        ";

        $queryBase = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('vouchers', function($join) {
                $join->on('vouchers.code', '=', 'harga_carts.voucher')
                     ->on('vouchers.event_uid', '=', 'events.uid');
            })
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

        $activeEvents = (clone $totalEvent)
            ->where('konfirmasi', '1')
            ->where('status', 'active')
            ->latest()
            ->get();

        // GRAPHIC ANALYTIC (Last 7 Days) - Like Admin Dashboard
        $last7Days = collect(range(6, 0))->map(function ($days) {
            return Carbon::now()->subDays($days)->format('Y-m-d');
        });

        $dailyData = (clone $queryBase)
            ->where('carts.created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(carts.created_at) as date'),
                DB::raw("SUM($rumusDasar) as revenue"),
                DB::raw("SUM(CASE WHEN carts.payment_type = 'cash' THEN harga_carts.quantity ELSE 0 END) as cash_qty"),
                DB::raw("SUM(CASE WHEN carts.payment_type != 'cash' THEN harga_carts.quantity ELSE 0 END) as noncash_qty")
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = $last7Days->map(fn ($date) => Carbon::parse($date)->format('d M'))->toArray();
        $chartRevenue = $last7Days->map(fn ($date) => (int) ($dailyData->has($date) ? $dailyData[$date]->revenue : 0))->toArray();
        $chartCashQty = $last7Days->map(fn ($date) => (int) ($dailyData->has($date) ? $dailyData[$date]->cash_qty : 0))->toArray();
        $chartNonCashQty = $last7Days->map(fn ($date) => (int) ($dailyData->has($date) ? $dailyData[$date]->noncash_qty : 0))->toArray();

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
                'revenue' => $chartRevenue,
                'cash' => $chartCashQty,
                'nonCash' => $chartNonCashQty,
            ],
            'gender' => $genderStats
        ]);
    }
}
