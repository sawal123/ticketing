<?php

namespace App\Livewire\Admin;

use App\Jobs\sendEmailETransaksi;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\Harga;
use App\Models\PaymentGateway;
use App\Services\Reports\FinancialSnapshotService;
use App\Services\Tickets\GateTokenService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class EventDetail extends Component
{
    use WithPagination;

    public $eventUid;

    public $activeTab = 'umum'; // umum, tiket, transaksi

    public $searchTransaction = '';

    public $showFullDescription = false;

    // Filters for Transactions
    public $filterPayment = 'all'; // all, cash, non-cash

    public $filterRange; // Format: "YYYY-MM-DD to YYYY-MM-DD"

    // For Edit Ticket Modal
    public $editingHargaId;

    public $editingHarga = [
        'kategori' => '',
        'qty' => 0,
        'harga' => 0,
    ];

    // For Delete Modal
    public $deletingHargaId;

    // For Transaction Detail Modal
    public $selectedTransactionId;

    // For Resend Email Confirmation
    public $resendEmailUid;

    public $paymentGatewayConfigs = [];

    protected $queryString = [
        'activeTab' => ['except' => 'umum'],
        'searchTransaction' => ['except' => ''],
        'filterPayment' => ['except' => 'all'],
        'filterRange' => ['except' => null],
    ];

    private function allowedPaymentFilters(): array
    {
        return ['all', 'cash', 'non-cash'];
    }

    private function allowedTabs(): array
    {
        $tabs = ['umum', 'tiket', 'transaksi'];

        if ($this->canManagePaymentTab()) {
            array_splice($tabs, 2, 0, ['pembayaran']);
        }

        return $tabs;
    }

    private function sanitizeFilters(): void
    {
        if (! in_array($this->filterPayment, $this->allowedPaymentFilters(), true)) {
            $this->filterPayment = 'all';
        }

        if (! in_array($this->activeTab, $this->allowedTabs(), true)) {
            $this->activeTab = 'umum';
        }

        $this->searchTransaction = mb_substr(trim((string) $this->searchTransaction), 0, 100);

        if ($this->filterRange !== null) {
            $this->filterRange = mb_substr(trim((string) $this->filterRange), 0, 32) ?: null;
        }

        if ($this->filterRange !== null && $this->normalizedDateRange() === null) {
            $this->filterRange = null;
            session()->flash('error', 'Filter tanggal tidak valid.');
        }
    }

    private function normalizedDateRange(): ?array
    {
        if (blank($this->filterRange)) {
            return null;
        }

        $dates = explode(' to ', (string) $this->filterRange);

        if (count($dates) > 2) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
            $end = isset($dates[1])
                ? Carbon::createFromFormat('Y-m-d', trim($dates[1]))->endOfDay()
                : $start->copy()->endOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($start->format('Y-m-d') !== trim($dates[0])) {
            return null;
        }

        if (isset($dates[1]) && $end->format('Y-m-d') !== trim($dates[1])) {
            return null;
        }

        if ($end->lt($start) || $start->diffInDays($end) > 366) {
            return null;
        }

        return [$start, $end];
    }

    public function mount($uid)
    {
        $this->eventUid = $uid;
    }

    private function canManagePaymentTab(): bool
    {
        return optional(Auth::user())->role === 'admin';
    }

    public function canSeePaymentTab(): bool
    {
        return $this->canManagePaymentTab();
    }

    private function authorizePaymentManagement(): void
    {
        abort_unless($this->canManagePaymentTab(), 403);
    }

    protected function getEventData()
    {
        return Event::with([
            'talents',
            'hargas' => function ($query) {
                $query->withSum(['hargaCarts as sold_count' => function ($q) {
                    $q->whereHas('cart', function ($c) {
                        $c->where('status', 'SUCCESS');
                    });
                }], 'quantity');
            },
        ])->where('uid', $this->eventUid)->firstOrFail();
    }

    protected function getCurrentEventModel(): Event
    {
        return Event::where('uid', $this->eventUid)->firstOrFail();
    }

    protected function getMetricsData()
    {
        $this->sanitizeFilters();

        $query = Cart::where('event_uid', $this->eventUid)->where('status', 'SUCCESS');
        $query = $this->applyFilters($query);

        $carts = (clone $query)->with('hargaCarts')->get();
        $totalTransactions = $carts->count();
        $snapshotTotals = app(FinancialSnapshotService::class)->collectionTotals($carts);

        $totalInternetFee = $query->sum('internet_fee');

        return [
            'total_transactions' => $totalTransactions,
            'total_revenue' => $snapshotTotals['owner_revenue'],
            'total_tickets' => $snapshotTotals['total_quantity'],
            'total_pajak' => $snapshotTotals['tax_total'],
            'total_internet_fee' => $totalInternetFee,
        ];
    }

    protected function authorizedTransactionQuery()
    {
        return Cart::query()
            ->where('event_uid', $this->eventUid);
    }

    protected function applyFilters($query)
    {
        $dateRange = $this->normalizedDateRange();

        return $query->when($this->filterPayment !== 'all', function ($q) {
            if ($this->filterPayment === 'cash') {
                $q->where('payment_type', 'cash');
            } else {
                $q->where('payment_type', '!=', 'cash');
            }
        })
            ->when($dateRange, function ($q) use ($dateRange) {
                $q->whereBetween('created_at', $dateRange);
            })
            ->when($this->searchTransaction, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('carts.invoice', 'like', '%'.$this->searchTransaction.'%')
                        ->orWhere(function ($online) {
                            $online->where('carts.payment_type', '!=', 'cash')
                                ->whereHas('users', function ($u) {
                                    $u->where(function ($userQuery) {
                                        $userQuery->where('name', 'like', '%'.$this->searchTransaction.'%')
                                            ->orWhere('email', 'like', '%'.$this->searchTransaction.'%');
                                    });
                                });
                        })
                        ->orWhere(function ($cashCart) {
                            $cashCart->where('carts.payment_type', 'cash')
                                ->whereHas('cashBuyer', function ($cash) {
                                    $cash->where(function ($cashQuery) {
                                        $cashQuery->where('name', 'like', '%'.$this->searchTransaction.'%')
                                            ->orWhere('email', 'like', '%'.$this->searchTransaction.'%');
                                    });
                                });
                        });
                });
            });
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->sanitizeFilters();

        if ($this->activeTab === 'pembayaran' && $this->canManagePaymentTab()) {
            $this->loadPaymentGatewayConfigs();
        }

        $this->resetPage();
    }

    protected function loadPaymentGatewayConfigs(): void
    {
        $this->authorizePaymentManagement();

        $event = $this->getCurrentEventModel();
        $event->load(['eventPaymentGateways' => function ($query) {
            $query->select([
                'id',
                'event_id',
                'payment_gateway_id',
                'is_active',
                'fee_mode',
                'fee_fixed',
                'fee_percent',
            ]);
        }]);

        $configs = [];

        foreach (PaymentGateway::orderBy('payment')->get() as $gateway) {
            $eventGateway = $event->eventPaymentGateways->firstWhere('payment_gateway_id', $gateway->id);

            $configs[$gateway->id] = [
                'is_active' => (bool) ($eventGateway?->is_active ?? false),
                'fee_mode' => $eventGateway?->fee_mode ?? EventPaymentGateway::FEE_MODE_GLOBAL,
                'fee_fixed' => $eventGateway?->fee_fixed !== null ? (string) $eventGateway->fee_fixed : '0',
                'fee_percent' => $eventGateway?->fee_percent !== null ? (string) $eventGateway->fee_percent : '0',
            ];
        }

        $this->paymentGatewayConfigs = $configs;
    }

    protected function validatePaymentGatewayConfig(int $gatewayId): array
    {
        PaymentGateway::findOrFail($gatewayId);

        return Validator::make(['paymentGatewayConfigs' => $this->paymentGatewayConfigs], [
            'paymentGatewayConfigs.'.$gatewayId.'.fee_mode' => 'required|in:global,manual',
            'paymentGatewayConfigs.'.$gatewayId.'.is_active' => 'required|boolean',
            'paymentGatewayConfigs.'.$gatewayId.'.fee_fixed' => [
                'nullable',
                'regex:/^\d{1,13}(\.\d{1,2})?$/',
            ],
            'paymentGatewayConfigs.'.$gatewayId.'.fee_percent' => [
                'nullable',
                'regex:/^\d{1,4}(\.\d{1,4})?$/',
            ],
        ])->validate();
    }

    public function setPaymentFeeMode(int $gatewayId, string $mode): void
    {
        $this->authorizePaymentManagement();

        PaymentGateway::findOrFail($gatewayId);

        if (! in_array($mode, [EventPaymentGateway::FEE_MODE_GLOBAL, EventPaymentGateway::FEE_MODE_MANUAL], true)) {
            abort(422);
        }

        if (! array_key_exists($gatewayId, $this->paymentGatewayConfigs)) {
            $this->loadPaymentGatewayConfigs();
        }

        $this->paymentGatewayConfigs[$gatewayId]['fee_mode'] = $mode;
    }

    public function toggleEventPaymentGateway(int $gatewayId): void
    {
        $this->authorizePaymentManagement();

        if (! array_key_exists($gatewayId, $this->paymentGatewayConfigs)) {
            $this->loadPaymentGatewayConfigs();
        }

        if (! array_key_exists($gatewayId, $this->paymentGatewayConfigs)) {
            abort(404);
        }

        $this->paymentGatewayConfigs[$gatewayId]['is_active'] = ! (bool) $this->paymentGatewayConfigs[$gatewayId]['is_active'];

        $this->saveEventPaymentGateway($gatewayId);
    }

    public function saveEventPaymentGateway(int $gatewayId): void
    {
        $this->authorizePaymentManagement();

        $validated = $this->validatePaymentGatewayConfig($gatewayId);
        $event = $this->getCurrentEventModel();
        $config = $validated['paymentGatewayConfigs'][$gatewayId];

        $eventPaymentGateway = EventPaymentGateway::firstOrNew([
            'event_id' => $event->id,
            'payment_gateway_id' => $gatewayId,
        ]);

        $eventPaymentGateway->is_active = (bool) $config['is_active'];
        $eventPaymentGateway->fee_mode = $config['fee_mode'];
        $eventPaymentGateway->fee_fixed = $config['fee_mode'] === EventPaymentGateway::FEE_MODE_MANUAL
            ? (float) ($config['fee_fixed'] ?? 0)
            : null;
        $eventPaymentGateway->fee_percent = $config['fee_mode'] === EventPaymentGateway::FEE_MODE_MANUAL
            ? (float) ($config['fee_percent'] ?? 0)
            : null;
        $eventPaymentGateway->save();

        $this->paymentGatewayConfigs[$gatewayId] = [
            'is_active' => (bool) $eventPaymentGateway->is_active,
            'fee_mode' => $eventPaymentGateway->fee_mode,
            'fee_fixed' => $eventPaymentGateway->fee_fixed !== null ? (string) $eventPaymentGateway->fee_fixed : '0',
            'fee_percent' => $eventPaymentGateway->fee_percent !== null ? (string) $eventPaymentGateway->fee_percent : '0',
        ];

        session()->flash('message', 'Konfigurasi pembayaran event berhasil disimpan.');
    }

    public function resetFilters()
    {
        $this->filterPayment = 'all';
        $this->filterRange = null;
        $this->searchTransaction = '';
        $this->resetPage();
    }

    public function toggleDescription()
    {
        $this->showFullDescription = ! $this->showFullDescription;
    }

    public function confirmEvent(): void
    {
        $event = Event::where('uid', $this->eventUid)->firstOrFail();

        if ((string) $event->konfirmasi === '1' && $event->status === 'active') {
            session()->flash('message', 'Event sudah aktif.');
            $this->dispatch('close-modal', name: 'confirm-event-modal');

            return;
        }

        $event->update([
            'konfirmasi' => '1',
            'status' => 'active',
        ]);

        session()->flash('message', 'Event berhasil dikonfirmasi dan diaktifkan.');
        $this->dispatch('close-modal', name: 'confirm-event-modal');
    }

    public function toggleTicketStatus($id)
    {
        $harga = Harga::findOrFail($id);
        $harga->status = $harga->status === 'active' ? 'inactive' : 'active';
        $harga->save();
        session()->flash('message', 'Status tiket berhasil diperbarui.');
    }

    public function confirmDeleteTicket($id)
    {
        $harga = Harga::findOrFail($id);
        $hasTransactions = $harga->hargaCarts()->whereHas('cart', function ($q) {
            $q->where('status', 'SUCCESS');
        })->exists();

        if ($hasTransactions) {
            $this->dispatch('open-modal', name: 'forbidden-delete-modal');

            return;
        }

        $this->deletingHargaId = $id;
        $this->dispatch('open-modal', name: 'delete-ticket-modal');
    }

    public function deleteTicket()
    {
        if ($this->deletingHargaId) {
            $harga = Harga::findOrFail($this->deletingHargaId);
            $harga->delete();
            $this->dispatch('close-modal', name: 'delete-ticket-modal');
            $this->deletingHargaId = null;
            session()->flash('message', 'Tiket berhasil dihapus.');
        }
    }

    public function editTicket($id)
    {
        $harga = Harga::findOrFail($id);
        $this->editingHargaId = $id;
        $this->editingHarga = [
            'kategori' => $harga->kategori,
            'qty' => $harga->qty,
            'harga' => $harga->harga,
        ];

        $this->dispatch('open-modal', name: 'edit-ticket-modal');
    }

    public function updateTicket()
    {
        $this->validate([
            'editingHarga.kategori' => 'required|string|max:255',
            'editingHarga.qty' => 'required|integer|min:0',
            'editingHarga.harga' => 'required|integer|min:0',
        ]);

        $harga = Harga::findOrFail($this->editingHargaId);
        $harga->update([
            'kategori' => $this->editingHarga['kategori'],
            'qty' => (int) $this->editingHarga['qty'],
            'harga' => (int) $this->editingHarga['harga'],
        ]);

        $this->dispatch('close-modal', name: 'edit-ticket-modal');
        session()->flash('message', 'Data tiket berhasil diperbarui.');
    }

    public function showTransactionDetail($uid)
    {
        $exists = $this->authorizedTransactionQuery()
            ->where('uid', $uid)
            ->where('status', 'SUCCESS')
            ->exists();

        if (! $exists) {
            $this->selectedTransactionId = null;
            session()->flash('error', 'Transaksi tidak ditemukan pada event ini.');

            return;
        }

        $this->selectedTransactionId = $uid;
        $this->dispatch('open-modal', name: 'transaction-detail-modal');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filterPayment', 'filterRange', 'searchTransaction', 'activeTab'])) {
            $this->sanitizeFilters();
            $this->resetPage();
        }
    }

    public function render()
    {
        $this->sanitizeFilters();

        $event = $this->getEventData();
        $metrics = $this->getMetricsData();
        $paymentGateways = collect();

        if ($this->activeTab === 'pembayaran' && $this->canManagePaymentTab()) {
            if ($this->paymentGatewayConfigs === []) {
                $this->loadPaymentGatewayConfigs();
            }

            $paymentGateways = PaymentGateway::with(['eventPaymentGateways' => function ($query) use ($event) {
                $query->where('event_id', $event->id);
            }])->orderBy('payment')->get();
        }

        $transactions = [];
        if ($this->activeTab === 'transaksi') {
            $transactions = $this->authorizedTransactionQuery()
                ->with(['users', 'cashBuyer'])
                ->where('status', 'SUCCESS');

            $transactions = $this->applyFilters($transactions)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        $selectedTransaction = null;
        $discount = 0;
        $voucherCode = null;

        if ($this->selectedTransactionId) {
            $selectedTransaction = $this->authorizedTransactionQuery()
                ->with(['users', 'cashBuyer', 'hargaCarts.masterHarga'])
                ->where('uid', $this->selectedTransactionId)
                ->where('status', 'SUCCESS')
                ->first();

            if ($selectedTransaction) {
                $hargaCartWithVoucher = $selectedTransaction->hargaCarts->whereNotNull('voucher')->first();
                if ($hargaCartWithVoucher) {
                    $voucherCode = $hargaCartWithVoucher->voucher;
                }
                $discount = $selectedTransaction->hargaCarts->sum(fn ($i) => (int) ($i->disc ?? 0));
            }
        }

        return view('livewire.admin.event-detail', [
            'event' => $event,
            'metrics' => $metrics,
            'canSeePaymentTab' => $this->canSeePaymentTab(),
            'paymentGateways' => $paymentGateways,
            'transactions' => $transactions,
            'selectedTransaction' => $selectedTransaction,
            'discount' => $discount,
            'voucherCode' => $voucherCode,
        ])->layout('admin.layout', ['title' => 'Detail Event: '.$event->event]);
    }

    public function confirmResendEmail($uid)
    {
        $this->resendEmailUid = $uid;
        $this->dispatch('open-modal', name: 'resend-email-modal');
    }

    /**
     * Resend the ticket barcode email to the buyer
     */
    public function resendEmail()
    {
        $uid = $this->resendEmailUid;
        if (! $uid) {
            return;
        }

        $cart = $this->authorizedTransactionQuery()
            ->with(['users', 'cashBuyer'])
            ->where('uid', $uid)
            ->first();
        if (! $cart) {
            session()->flash('error', 'Transaksi tidak ditemukan pada event ini.');

            return;
        }

        if ($cart->status !== 'SUCCESS') {
            session()->flash('error', 'Email hanya dapat dikirim ulang untuk transaksi yang sukses.');

            return;
        }

        if ($cart->scanned_at || (string) $cart->konfirmasi === '1') {
            session()->flash('error', 'Tiket sudah digunakan dan tidak dapat dikirim ulang.');

            return;
        }

        try {
            if ($cart->payment_type === 'cash') {
                $cash = $cart->cashBuyer;
                if ($cash) {
                    if (! filter_var($cash->email, FILTER_VALIDATE_EMAIL)) {
                        session()->flash('error', 'Email pembeli cash tidak valid atau kosong.');

                        return;
                    }

                    dispatch(new sendEmailTrnsaksi($cash->email, $cash->name, $cart->uid, true));
                } else {
                    session()->flash('error', 'Data pembeli tunai tidak ditemukan.');

                    return;
                }
            } else {
                $user = $cart->users;
                if ($user) {
                    app(GateTokenService::class)->ensureTicketAccessReady($cart);
                    $cart->refresh();
                    dispatch(new sendEmailETransaksi($user, $cart, true));
                } else {
                    session()->flash('error', 'Data pembeli tidak ditemukan.');

                    return;
                }
            }

            $this->dispatch('close-modal', name: 'resend-email-modal');
            $this->resendEmailUid = null;
            session()->flash('message', 'Email barcode telah dijadwalkan untuk dikirim.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal mengirim email: '.$e->getMessage());
        }
    }
}
