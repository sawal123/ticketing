<?php

namespace App\Livewire\Dashboard;

use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Partner;
use App\Models\Transaction;
use App\Services\Reports\FinancialSnapshotService;
use App\Services\Tickets\GateTokenService;
use App\Services\Tutorials\GettingStartedChecklistService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

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

    public $isDirectEntry = false;

    public $partnerId;

    public array $cashTransactionResult = [];

    public function toggleSellModal()
    {
        $this->resetCashForm();
        $this->isSellModalOpen = ! $this->isSellModalOpen;
    }

    public function resetCashForm()
    {
        $this->reset(['selectedEventId', 'selectedEvent', 'availableTickets', 'selectedTickets', 'buyerName', 'buyerEmail', 'buyerBirthday', 'buyerGender', 'partnerId']);
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
        return collect($this->selectedTickets)->sum(function ($item) {
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
            'partnerId' => 'nullable|string|max:100',
        ]);

        try {
            $emailPayload = null;
            $result = null;

            DB::transaction(function () use (&$emailPayload, &$result) {
                $user = auth()->user();
                $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
                $partnerUid = filled($this->partnerId) ? (string) $this->partnerId : null;
                $partner = null;

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

                if ($partnerUid !== null) {
                    $partner = Partner::query()
                        ->where('uid', $partnerUid)
                        ->where('user_uid', $ownerId)
                        ->where('status', 'active')
                        ->first();

                    if (! $partner) {
                        throw ValidationException::withMessages([
                            'partnerId' => 'Partner tidak valid.',
                        ]);
                    }
                }

                $subtotal = collect($validatedTickets)->sum(fn ($item) => (int) $item['model']->harga * $item['qty']);
                $quantity = collect($validatedTickets)->sum('qty');
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
                    'uid_partner' => $partner?->uid,
                    'uid_user' => $user->uid,
                    'uid_event' => $event->uid,
                    'name' => $this->buyerName,
                    'email' => $this->buyerEmail,
                    'nomor' => '080000000000',
                    'alamat' => '-',
                    'lahir' => $this->buyerBirthday,
                    'gender' => $this->buyerGender,
                ]);

                app(GateTokenService::class)->issueIfEnabled($cart);

                $emailPayload = [
                    'email' => $this->buyerEmail,
                    'name' => $this->buyerName,
                    'cart_uid' => $cart->uid,
                ];

                $result = [
                    'uid' => $cart->uid,
                    'event_uid' => $event->uid,
                    'event_name' => $event->event,
                    'invoice' => $cart->invoice,
                    'buyer_name' => $this->buyerName,
                    'buyer_email' => $this->buyerEmail,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'payment_status' => 'Lunas',
                    'attendance_status' => $this->isDirectEntry ? 'Hadir' : 'Belum Hadir',
                    'email_status' => 'pending',
                ];
            }, 3);

            $this->dispatch('close-modal', name: 'sell-modal');
            $this->cashTransactionResult = $result;

            try {
                dispatch(new sendEmailTrnsaksi(
                    $emailPayload['email'],
                    $emailPayload['name'],
                    $emailPayload['cart_uid'],
                ));

                $this->cashTransactionResult['email_status'] = 'scheduled';
                $this->cashTransactionResult['email_message'] = 'Email barcode telah dijadwalkan.';
                session()->flash('success', 'Transaksi Cash Berhasil! Email barcode telah dijadwalkan untuk dikirim.');
            } catch (Throwable $mailException) {
                Log::error('Gagal menjadwalkan email barcode cash.', [
                    'cart_uid' => $emailPayload['cart_uid'] ?? null,
                    'recipient' => $emailPayload['email'] ?? null,
                    'error' => $mailException->getMessage(),
                ]);

                $this->cashTransactionResult['email_status'] = 'failed';
                $this->cashTransactionResult['email_message'] = 'Email perlu dikirim ulang.';
                session()->flash('success', 'Transaksi Cash Berhasil, tetapi email barcode perlu dikirim ulang.');
            }

            $this->selectedTickets = [];
            $this->loadAvailableTickets();
            $this->dispatch('open-modal', name: 'cash-transaction-success-modal');
        } catch (ValidationException $e) {
            $this->loadAvailableTickets();
            throw $e;
        } catch (Throwable $e) {
            $this->loadAvailableTickets();
            session()->flash('error', 'Gagal: '.$e->getMessage());
        }
    }

    public function startAnotherCashTransaction()
    {
        $eventId = $this->cashTransactionResult['event_uid'] ?? $this->selectedEventId;
        $this->dispatch('close-modal', name: 'cash-transaction-success-modal');

        if ($eventId) {
            $this->selectEvent($eventId);
        }

        $this->selectedTickets = [];
        $this->buyerName = null;
        $this->buyerEmail = null;
        $this->buyerBirthday = null;
        $this->buyerGender = null;
        $this->partnerId = null;
        $this->isDirectEntry = false;
        $this->cashTransactionResult = [];
        $this->loadAvailableTickets();
        $this->dispatch('open-modal', name: 'sell-modal');
    }

    public function viewLastCashTransaction()
    {
        if (empty($this->cashTransactionResult['event_uid']) || empty($this->cashTransactionResult['invoice'])) {
            return;
        }

        return redirect()->to(route('dashboard.event.detail', $this->cashTransactionResult['event_uid'])
            .'?activeTab=transaksi&filterPayment=cash&searchTransaction='
            .urlencode($this->cashTransactionResult['invoice']));
    }

    public function closeCashTransactionSuccess()
    {
        $this->dispatch('close-modal', name: 'cash-transaction-success-modal');
        $this->cashTransactionResult = [];
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
        $this->isGenderModalOpen = ! $this->isGenderModalOpen;
    }

    public function dismissGettingStartedChecklist()
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        app(GettingStartedChecklistService::class)->dismiss($user);
    }

    private function snapshotCartQuery(?string $ownerId = null)
    {
        $query = Cart::query()
            ->from('carts')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', Cart::STATUS_SUCCESS)
            ->whereNull('carts.deleted_at');

        if ($ownerId !== null) {
            $query->where('events.user_uid', $ownerId);
        }

        app(FinancialSnapshotService::class)->joinLineSnapshots($query);

        return $query;
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
        $ownerScope = $isAdmin ? null : $ownerId;
        $snapshot = app(FinancialSnapshotService::class);
        $ownerRevenueExpression = $snapshot->ownerRevenueSqlExpression();
        $gettingStarted = app(GettingStartedChecklistService::class)->buildForUser($user);

        $availablePartners = Partner::query()
            ->where('user_uid', $ownerId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // STATISTIK UTAMA
        $stats = $this->snapshotCartQuery($ownerScope)->select(
            DB::raw("SUM($ownerRevenueExpression) as total_omset")
        )->first();

        $totalTiket = (int) $this->snapshotCartQuery($ownerScope)
            ->selectRaw('SUM(COALESCE(line_snapshots.total_quantity, 0)) as total_tiket')
            ->value('total_tiket');

        $totalTransaksi = (int) Cart::query()
            ->from('carts')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', Cart::STATUS_SUCCESS)
            ->whereNull('carts.deleted_at')
            ->when(! $isAdmin, fn ($query) => $query->where('events.user_uid', $ownerId))
            ->distinct('carts.uid')
            ->count('carts.uid');

        $totalEvent = Event::query();
        if (! $isAdmin) {
            $totalEvent->where('user_uid', $ownerId);
        }
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

        $dailyData = $this->snapshotCartQuery($ownerScope)
            ->where('carts.created_at', '>=', now()->subDays(7)->startOfDay())
            ->select(
                DB::raw('DATE(carts.created_at) as date'),
                DB::raw("SUM($ownerRevenueExpression) as revenue"),
                DB::raw("SUM(CASE WHEN carts.payment_type = 'cash' THEN COALESCE(line_snapshots.total_quantity, 0) ELSE 0 END) as cash_qty"),
                DB::raw("SUM(CASE WHEN carts.payment_type IS NULL OR carts.payment_type != 'cash' THEN COALESCE(line_snapshots.total_quantity, 0) ELSE 0 END) as noncash_qty")
            )
            ->groupBy(DB::raw('DATE(carts.created_at)'))
            ->get()
            ->keyBy('date');

        $chartLabels = $last7Days->map(fn ($date) => Carbon::parse($date)->format('d M'))->toArray();
        $chartRevenue = $last7Days->map(fn ($date) => (int) ($dailyData->has($date) ? $dailyData[$date]->revenue : 0))->toArray();
        $chartCashQty = $last7Days->map(fn ($date) => (int) ($dailyData->has($date) ? $dailyData[$date]->cash_qty : 0))->toArray();
        $chartNonCashQty = $last7Days->map(fn ($date) => (int) ($dailyData->has($date) ? $dailyData[$date]->noncash_qty : 0))->toArray();

        // GENDER & AGE DEMOGRAPHICS
        $demographics = Cart::query()
            ->from('carts')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('users', 'users.uid', '=', 'carts.user_uid')
            ->leftJoin('cashes', 'cashes.uid', '=', 'carts.uid')
            ->where('carts.status', Cart::STATUS_SUCCESS)
            ->whereNull('carts.deleted_at')
            ->when(! $isAdmin, fn ($query) => $query->where('events.user_uid', $ownerId))
            ->selectRaw("
                CASE WHEN carts.payment_type = 'cash' THEN cashes.gender ELSE users.gender END as gender,
                CASE WHEN carts.payment_type = 'cash' THEN cashes.lahir ELSE users.birthday END as birthday
            ")
            ->get()
            ->map(function ($user) {
                try {
                    $user->age = filled($user->birthday) ? Carbon::parse($user->birthday)->age : null;
                } catch (Throwable) {
                    $user->age = null;
                }

                return $user;
            });

        $genderStats = [
            'pria' => $demographics->where('gender', 'pria')->count(),
            'wanita' => $demographics->where('gender', 'wanita')->count(),
            'age_18_25' => $demographics->filter(fn ($item) => $item->age !== null && $item->age >= 18 && $item->age <= 25)->count(),
            'age_gt_25' => $demographics->filter(fn ($item) => $item->age !== null && $item->age > 25)->count(),
            'age_lt_18' => $demographics->filter(fn ($item) => $item->age !== null && $item->age < 18)->count(),
        ];

        return view('livewire.dashboard.demo-index', [
            'title' => 'Dashboard Overview',
            'stats' => [
                'omset' => $stats->total_omset ?? 0,
                'tiket' => $totalTiket,
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
            'gender' => $genderStats,
            'gettingStarted' => $gettingStarted,
            'dashboardTourSteps' => $user->role === 'penyewa' && Schema::hasTable('tutorial_progress')
                ? $this->dashboardTourSteps()
                : [],
        ]);
    }

    private function dashboardTourSteps(): array
    {
        return [
            [
                'target' => '[data-tour="dashboard-help"]',
                'title' => 'Panduan Singkat',
                'description' => 'Buka Panduan kapan saja untuk melihat bantuan ringkas sesuai halaman yang sedang Anda gunakan.',
                'placement' => 'bottom',
            ],
            [
                'target' => '[data-tour="dashboard-revenue"]',
                'title' => 'Ringkasan Keuangan',
                'description' => 'Total Omset merangkum nilai penjualan dari event Anda.',
                'placement' => 'bottom',
            ],
            [
                'target' => '[data-tour="dashboard-transactions"]',
                'title' => 'Total Transaksi',
                'description' => 'Angka ini merangkum transaksi berhasil. Detail transaksi dapat dibuka dari event atau menu Transaksi.',
                'placement' => 'bottom',
            ],
            [
                'target' => '[data-tour="dashboard-active-events"]',
                'title' => 'Event Aktif',
                'description' => 'Dari bagian ini Anda dapat membuka Detail Event serta transaksi online atau cash untuk event aktif.',
                'placement' => 'left',
            ],
            [
                'target' => '[data-tour="dashboard-sales-trend"]',
                'title' => 'Tren Penjualan',
                'description' => 'Grafik ini menampilkan tren penjualan tujuh hari terakhir.',
                'placement' => 'top',
            ],
            [
                'target' => '[data-tour="dashboard-revenue"]',
                'title' => 'Penarikan Saldo',
                'description' => 'Untuk mengajukan pencairan saldo, buka menu Penarikan Saldo dari navigasi dashboard.',
                'placement' => 'bottom',
            ],
        ];
    }
}
