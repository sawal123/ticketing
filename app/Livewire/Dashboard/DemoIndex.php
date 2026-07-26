<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\Harga;
use App\Models\Partner;
use App\Models\Cash;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
    public $selectedTickets = []; // Array of ['id' => id, 'name' => name, 'price' => price, 'qty' => 1, 'max_qty' => stock]
    
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
        if (! $ticketId) {
            return;
        }

        $ticket = Harga::where('id', $ticketId)
            ->where('uid', $this->selectedEventId)
            ->where('status', 'active')
            ->first();

        $remainingStock = $ticket ? $this->getRemainingStock($ticket) : 0;

        if (! $ticket || $remainingStock < 1) {
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
                'max_qty' => $remainingStock,
            ];
        }

        $this->loadAvailableTickets();
    }

    public function increaseTicketQty($index)
    {
        if (! isset($this->selectedTickets[$index])) {
            return;
        }

        $this->refreshSelectedTicketStock($index);
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

    public function updatedSelectedTickets($value, $key)
    {
        if (! Str::endsWith($key, '.qty')) {
            return;
        }

        $index = (int) Str::before($key, '.');

        if (! isset($this->selectedTickets[$index])) {
            return;
        }

        $this->refreshSelectedTicketStock($index);

        $maxQty = max(1, (int) ($this->selectedTickets[$index]['max_qty'] ?? 1));
        $qty = filter_var($value, FILTER_VALIDATE_INT);
        $this->selectedTickets[$index]['qty'] = min($maxQty, max(1, $qty === false ? 1 : $qty));
    }

    public function removeTicket($index)
    {
        unset($this->selectedTickets[$index]);
        $this->selectedTickets = array_values($this->selectedTickets);
        $this->loadAvailableTickets();
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
            $emailPayload = null;

            DB::transaction(function () use (&$emailPayload) {
                $user = auth()->user();
                $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

                $event = Event::where('uid', $this->selectedEventId)
                    ->where('konfirmasi', '1')
                    ->where('status', 'active')
                    ->when($user->role !== 'admin', fn ($query) => $query->where('user_uid', $ownerId))
                    ->lockForUpdate()
                    ->firstOrFail();

                $ticketIds = collect($this->selectedTickets)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();

                $tickets = Harga::whereIn('id', $ticketIds)
                    ->where('uid', $event->uid)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($tickets->count() !== $ticketIds->count()) {
                    throw ValidationException::withMessages([
                        'selectedTickets' => 'Kategori tiket tidak valid untuk event ini.',
                    ]);
                }

                $validatedTickets = [];
                foreach ($this->selectedTickets as $item) {
                    $ticket = $tickets->get((int) $item['id']);
                    $requestedQty = filter_var($item['qty'], FILTER_VALIDATE_INT);
                    $remainingStock = $ticket ? $ticket->remainingQty() : 0;

                    if (! $ticket || $ticket->status !== 'active' || $requestedQty === false || $requestedQty < 1 || $requestedQty > $remainingStock) {
                        throw ValidationException::withMessages([
                            'selectedTickets' => "Tiket {$ticket?->kategori} tidak aktif atau stok tidak mencukupi.",
                        ]);
                    }

                    $validatedTickets[] = [
                        'model' => $ticket,
                        'qty' => $requestedQty,
                    ];
                }

                $subtotal = collect($validatedTickets)->sum(fn ($item) => (int) $item['model']->harga * $item['qty']);
                $taxPercent = (int) ($event->fee ?? 0);
                $tax = (int) round(($taxPercent / 100) * $subtotal);
                $total = $subtotal + $tax;
                $invoice = $this->generateCashInvoice();
                $cartUid = (string) Str::uuid();

                $cart = Cart::create([
                    'uid' => $cartUid,
                    'user_uid' => $user->uid,
                    'event_uid' => $event->uid,
                    'invoice' => $invoice,
                    'status' => Cart::STATUS_SUCCESS,
                    'konfirmasi' => $this->isDirectEntry ? '1' : null,
                    'payment_type' => 'cash',
                    'gross_amount' => $total,
                    'pajak' => $tax,
                    'pajak_persen' => $taxPercent,
                    'paid_at' => now(),
                    'reservation_released_at' => now(),
                    'midtrans_status' => Cart::STATUS_SUCCESS,
                ]);

                foreach ($validatedTickets as $index => $item) {
                    $ticket = $item['model'];

                    HargaCart::create([
                        'uid' => $cart->uid,
                        'orderBy' => (string) ($index + 1),
                        'harga_id' => $ticket->id,
                        'event_uid' => $event->uid,
                        'quantity' => $item['qty'],
                        'harga_ticket' => (int) $ticket->harga,
                        'kategori_harga' => $ticket->kategori,
                        'voucher' => null,
                        'disc' => 0,
                    ]);

                    $ticket->sold_qty = (int) $ticket->sold_qty + $item['qty'];
                    $ticket->save();
                }

                Transaction::create([
                    'uid' => $cart->uid,
                    'user_uid' => $user->uid,
                    'event_uid' => $event->uid,
                    'amount' => (string) $total,
                    'gross_amount' => $total,
                    'invoice' => $invoice,
                    'payment_type' => 'cash',
                    'status_transaksi' => Cart::STATUS_SUCCESS,
                    'paid_at' => now(),
                ]);

                Cash::create([
                    'uid' => $cart->uid,
                    'uid_partner' => $this->partnerId,
                    'uid_user' => $user->uid,
                    'uid_event' => $event->uid,
                    'name' => $this->buyerName,
                    'email' => $this->buyerEmail,
                    'nomor' => '080000000000',
                    'alamat' => '-',
                    'lahir' => $this->buyerBirthday,
                    'gender' => $this->buyerGender,
                ]);

                $emailPayload = [
                    'email' => $this->buyerEmail,
                    'name' => $this->buyerName,
                    'cart_uid' => $cart->uid,
                    'barcode' => $cart->invoice,
                ];
            }, 3);

            $this->dispatch('close-modal', name: 'sell-modal');

            try {
                dispatch(new sendEmailTrnsaksi(
                    $emailPayload['email'],
                    $emailPayload['name'],
                    $emailPayload['cart_uid'],
                    $emailPayload['barcode'],
                ));

                session()->flash('success', 'Transaksi Cash Berhasil! Email barcode telah dijadwalkan untuk dikirim.');
            } catch (\Throwable $mailException) {
                Log::error('Gagal menjadwalkan email barcode cash.', [
                    'cart_uid' => $emailPayload['cart_uid'] ?? null,
                    'recipient' => $emailPayload['email'] ?? null,
                    'error' => $mailException->getMessage(),
                ]);

                session()->flash('success', 'Transaksi Cash Berhasil, tetapi email barcode perlu dikirim ulang.');
            }

            return redirect()->route('dashboard');

        } catch (ValidationException $e) {
            $this->loadAvailableTickets();
            throw $e;
        } catch (\Throwable $e) {
            $this->loadAvailableTickets();
            session()->flash('error', 'Gagal: ' . $e->getMessage());
        }
    }

    protected function loadAvailableTickets()
    {
        $this->availableTickets = Harga::where('uid', $this->selectedEventId)
            ->get()
            ->map(fn (Harga $ticket) => [
                'id' => $ticket->id,
                'kategori' => $ticket->kategori,
                'harga' => (int) $ticket->harga,
                'status' => $ticket->status,
                'remaining_stock' => $ticket->remainingQty(),
            ])
            ->values()
            ->all();
    }

    protected function getRemainingStock(Harga $ticket)
    {
        return $ticket->remainingQty();
    }

    protected function refreshSelectedTicketStock(int $index): void
    {
        $ticketId = $this->selectedTickets[$index]['id'] ?? null;

        if (! $ticketId) {
            return;
        }

        $ticket = Harga::where('id', $ticketId)
            ->where('uid', $this->selectedEventId)
            ->first();

        if (! $ticket) {
            $this->selectedTickets[$index]['max_qty'] = 0;
            $this->selectedTickets[$index]['qty'] = 1;
            return;
        }

        $maxQty = $ticket->status === 'active' ? $ticket->remainingQty() : 0;
        $this->selectedTickets[$index]['max_qty'] = $maxQty;
        $this->selectedTickets[$index]['qty'] = min(max(1, (int) $this->selectedTickets[$index]['qty']), max(1, $maxQty));
    }

    protected function generateCashInvoice(): string
    {
        do {
            $invoice = 'CASH-'.now()->format('Ymd').Str::upper(Str::random(10));
        } while (Cart::where('invoice', $invoice)->exists());

        return $invoice;
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
            ->select('users.gender', 'users.birthday')
            ->get()
            ->map(function ($user) {
                $user->age = filled($user->birthday) ? Carbon::parse($user->birthday)->age : null;

                return $user;
            });

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
