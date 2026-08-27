<?php

namespace App\Livewire\Dashboard;

use App\Jobs\sendEmailETransaksi;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Agreement;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Talent;
use App\Services\Agreements\AgreementPreviewService;
use App\Services\Agreements\AgreementSignedUploadService;
use App\Services\Reports\FinancialSnapshotService;
use App\Services\SecureImageStorage;
use App\Services\Tickets\GateTokenService;
use App\Support\ExportSanitizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EventDetail extends Component
{
    use WithFileUploads;
    use WithPagination;

    private const BLOCKING_TRANSACTION_STATUSES = [
        Cart::STATUS_SUCCESS,
        Cart::STATUS_PENDING,
        Cart::STATUS_RESERVED,
        Cart::STATUS_PAYMENT_REVIEW,
        Cart::STATUS_UNPAID,
    ];

    private const LOCKED_QTY_STATUSES = [
        Cart::STATUS_SUCCESS,
        Cart::STATUS_RESERVED,
        Cart::STATUS_PENDING,
        Cart::STATUS_PAYMENT_REVIEW,
        Cart::STATUS_UNPAID,
    ];

    #[Layout('layouts.unified')]
    #[Locked]
    public $eventUid;

    public $activeTab = 'umum'; // umum, tiket, mou, transaksi

    public $searchTransaction = '';

    public $perPage = 10;

    public $showFullDescription = false;

    // Signed MOU upload (M9)
    public $signedMou;

    // Filters for Transactions
    public $filterPayment = 'all'; // all, cash, non-cash

    public $filterRange; // Format: "YYYY-MM-DD to YYYY-MM-DD"

    // For Add/Edit Ticket Modal
    public $newHarga = [
        'kategori' => '',
        'qty' => 0,
        'harga' => 0,
    ];

    public $editingHargaId;

    public $editingHarga = [
        'kategori' => '',
        'qty' => 0,
        'harga' => 0,
    ];

    // For Delete Modal
    public $deletingHargaId;

    public $deletingTalentId;

    // For Transaction Detail Modal
    public $selectedTransactionId;

    // For Resend Email Confirmation
    public $resendEmailUid;

    // For Add/Edit Talent
    public $editingTalentId;

    public $talentName;

    public $talentLink;

    public $talentImage;

    public $existingTalentImage;

    protected $queryString = [
        'activeTab' => ['except' => 'umum'],
        'perPage' => ['except' => 10],
        'searchTransaction' => ['except' => ''],
        'filterPayment' => ['except' => 'all'],
        'filterRange' => ['except' => null],
    ];

    private function allowedPerPage(): array
    {
        return [10, 25, 50, 100];
    }

    private function allowedPaymentFilters(): array
    {
        return ['all', 'cash', 'non-cash'];
    }

    private function allowedTabs(): array
    {
        return ['umum', 'tiket', 'mou', 'transaksi'];
    }

    private function sanitizeFilters(): void
    {
        $this->perPage = (int) $this->perPage;
        if (! in_array($this->perPage, $this->allowedPerPage(), true)) {
            $this->perPage = 10;
        }

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

    protected function getEventData()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $query = Event::with([
            'talents',
            'hargas' => function ($query) {
                $query->withSum(['hargaCarts as sold_count' => function ($q) {
                    $q->whereHas('cart', function ($c) {
                        $c->where('status', 'SUCCESS');
                    });
                }], 'quantity');
            },
        ])->where('uid', $this->eventUid);

        if (! $isAdmin) {
            $query->where('user_uid', $ownerId);
        }

        return $query->firstOrFail();
    }

    protected function getMetricsData()
    {
        $this->sanitizeFilters();

        $query = Cart::where('event_uid', $this->eventUid)->where('status', 'SUCCESS');
        $query = $this->applyFilters($query);

        $carts = (clone $query)->with('hargaCarts')->get();
        $totalTransactions = $carts->count();
        $snapshotTotals = app(FinancialSnapshotService::class)->collectionTotals($carts);
        $totalInternetFee = (clone $query)->sum('internet_fee');

        return [
            'total_transactions' => $totalTransactions,
            'total_revenue' => $snapshotTotals['owner_revenue'],
            'total_tickets' => $snapshotTotals['total_quantity'],
            'total_pajak' => $snapshotTotals['tax_total'],
            'total_internet_fee' => $totalInternetFee,
            'total_discount' => $snapshotTotals['discount_total'],
        ];
    }

    protected function authorizedTransactionQuery()
    {
        $this->getEventData();

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
                $q->whereBetween('carts.created_at', $dateRange);
            })
            ->when($this->searchTransaction, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('carts.invoice', 'like', '%' . $this->searchTransaction . '%')
                        ->orWhere(function ($online) {
                            $online->where('carts.payment_type', '!=', 'cash')
                                ->whereHas('users', function ($u) {
                                    $u->where(function ($userQuery) {
                                        $userQuery->where('name', 'like', '%' . $this->searchTransaction . '%')
                                            ->orWhere('email', 'like', '%' . $this->searchTransaction . '%');
                                    });
                                });
                        })
                        ->orWhere(function ($cashCart) {
                            $cashCart->where('carts.payment_type', 'cash')
                                ->whereHas('cashBuyer', function ($cash) {
                                    $cash->where(function ($cashQuery) {
                                        $cashQuery->where('name', 'like', '%' . $this->searchTransaction . '%')
                                            ->orWhere('email', 'like', '%' . $this->searchTransaction . '%');
                                    });
                                });
                        });
                });
            });
    }

    /**
     * Optimized query for exports (CSV/Print)
     * Avoids N+1 and minimizes object hydration
     */
    protected function getExportQuery()
    {
        $this->sanitizeFilters();
        $dateRange = $this->normalizedDateRange();
        $snapshots = app(FinancialSnapshotService::class);

        $query = DB::table('carts')
            ->join('users', 'users.uid', '=', 'carts.user_uid')
            ->leftJoin('cashes', 'cashes.uid', '=', 'carts.uid')
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid');

        $snapshots->joinLineSnapshots($query);

        $query
            ->select([
                'carts.created_at',
                'carts.uid as cart_uid',
                'carts.invoice',
                DB::raw("CASE WHEN carts.payment_type = 'cash' THEN COALESCE(cashes.name, 'Data Pembeli Tidak Ditemukan') ELSE users.name END as buyer_name"),
                DB::raw("CASE WHEN carts.payment_type = 'cash' THEN COALESCE(cashes.email, '-') ELSE users.email END as buyer_email"),
                'harga_carts.kategori_harga',
                'harga_carts.quantity',
                'harga_carts.harga_ticket',
                'harga_carts.disc',
                'carts.payment_type',
                'carts.konfirmasi',
                'carts.scanned_at',
                'carts.pajak',
                'carts.internet_fee',
                'carts.gross_amount',
                DB::raw($snapshots->taxSnapshotSqlExpression() . ' as tax_snapshot'),
            ])
            ->where('carts.event_uid', $this->eventUid)
            ->where('carts.status', 'SUCCESS')
            ->whereNull('carts.deleted_at')
            ->whereNull('harga_carts.deleted_at');

        // Apply same filters as UI
        if ($this->filterPayment !== 'all') {
            if ($this->filterPayment === 'cash') {
                $query->where('carts.payment_type', 'cash');
            } else {
                $query->where('carts.payment_type', '!=', 'cash');
            }
        }

        if ($dateRange) {
            $query->whereBetween('carts.created_at', $dateRange);
        }

        if ($this->searchTransaction) {
            $query->where(function ($q) {
                $q->where('carts.invoice', 'like', '%' . $this->searchTransaction . '%')
                    ->orWhere(function ($online) {
                        $online->where('carts.payment_type', '!=', 'cash')
                            ->where(function ($user) {
                                $user->where('users.name', 'like', '%' . $this->searchTransaction . '%')
                                    ->orWhere('users.email', 'like', '%' . $this->searchTransaction . '%');
                            });
                    })
                    ->orWhere(function ($cash) {
                        $cash->where('carts.payment_type', 'cash')
                            ->where(function ($cashBuyer) {
                                $cashBuyer->where('cashes.name', 'like', '%' . $this->searchTransaction . '%')
                                    ->orWhere('cashes.email', 'like', '%' . $this->searchTransaction . '%');
                            });
                    });
            });
        }

        return $query->orderBy('carts.created_at', 'desc');
    }

    protected function exportSnapshotTotalsFromRows($rows): array
    {
        $cartUids = $rows->pluck('cart_uid')->filter()->unique()->values();

        if ($cartUids->isEmpty()) {
            return [
                'ticket_total' => 0,
                'discount_total' => 0,
                'total_quantity' => 0,
                'tax_total' => 0,
                'owner_revenue' => 0,
            ];
        }

        $carts = Cart::with('hargaCarts')
            ->whereIn('uid', $cartUids)
            ->get();

        return app(FinancialSnapshotService::class)->collectionTotals($carts);
    }

    public function exportExcel()
    {
        $this->sanitizeFilters();

        $fileName = 'transaksi-event-' . Str::slug($this->getEventData()->event) . '-' . now()->format('YmdHis') . '.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fwrite($file, "sep=;\r\n");
            $rows = $this->getExportQuery()->get();
            $exportTotals = $this->exportSnapshotTotalsFromRows($rows);
            $seenCartTaxes = [];

            // Header Row
            fputcsv($file, ['Tanggal', 'Invoice', 'Nama Pembeli', 'Email', 'Kategori Tiket', 'Qty', 'Harga Satuan', 'Diskon', 'Pajak Snapshot', 'Total Item Snapshot', 'Status Kehadiran', 'Status Verifikasi', 'Tanggal Verifikasi', 'Waktu Verifikasi'], ';');

            // Data Rows (Optimized with cursor)
            $rows->each(function ($row) use ($file, &$seenCartTaxes) {
                $lineTotal = ((int) $row->quantity * (int) $row->harga_ticket) - (int) ($row->disc ?? 0);
                $taxSnapshot = isset($seenCartTaxes[$row->cart_uid]) ? 0 : (int) ($row->tax_snapshot ?? 0);
                $seenCartTaxes[$row->cart_uid] = true;
                $scannedAt = filled($row->scanned_at) ? Carbon::parse($row->scanned_at) : null;
                $isVerified = $scannedAt !== null || (string) $row->konfirmasi === '1';

                fputcsv($file, ExportSanitizer::csvRow([
                    $row->created_at,
                    $row->invoice,
                    $row->buyer_name,
                    $row->buyer_email,
                    $row->kategori_harga,
                    (int) $row->quantity,
                    (int) $row->harga_ticket,
                    (int) ($row->disc ?? 0),
                    $taxSnapshot,
                    $lineTotal + $taxSnapshot,
                    $row->konfirmasi == '1' ? 'Hadir' : 'Belum Hadir',
                    $isVerified ? 'Terverifikasi' : 'Belum Diverifikasi',
                    $scannedAt ? $scannedAt->format('d M Y') : 'Tidak tersedia',
                    $scannedAt ? $scannedAt->format('H:i:s') : 'Tidak tersedia',
                ]), ';');
            });

            fputcsv($file, ExportSanitizer::csvRow(['', '', '', '', '', '', '', '', 'TOTAL PAJAK SELURUH DATA', (int) $exportTotals['tax_total'], '', '', '', '']), ';');
            fputcsv($file, ExportSanitizer::csvRow(['', '', '', '', '', '', '', '', 'TOTAL OMZET SELURUH DATA', (int) $exportTotals['owner_revenue'], '', '', '', '']), ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->sanitizeFilters();
        $this->resetPage();
    }

    /**
     * Upload / replace the tenant-signed MOU PDF while the Agreement is READY.
     */
    public function uploadSignedMou()
    {
        $this->getEventData();

        // Staff may view the event but never touch legal MOU documents (M9).
        if (strtolower((string) auth()->user()->role) !== 'penyewa') {
            abort(403, 'Hanya pemilik event yang dapat mengunggah dokumen MOU.');
        }

        $validated = $this->validate([
            'signedMou' => 'required|file|mimes:pdf|max:10240',
        ]);

        $event = $this->getEventData();
        $actor = auth()->user();

        try {
            $activeAgreement = $event->agreements()
                ->where('status', Agreement::STATUS_READY)
                ->orderByRaw("CASE WHEN type = 'addendum' THEN 2 ELSE 1 END DESC")
                ->orderByDesc('version')
                ->latest('id')
                ->first();

            $result = app(AgreementSignedUploadService::class)
                ->storeForEvent($event, $actor->uid, $validated['signedMou'], $activeAgreement?->uid);

            if (! $result['ok']) {
                session()->flash('error', $result['message'] ?? 'Upload dokumen bertanda tangan gagal.');
                $this->reset('signedMou');

                return;
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Upload dokumen bertanda tangan gagal: '.$e->getMessage());
            $this->reset('signedMou');

            return;
        }

        $this->reset('signedMou');
        $docType = $result['agreement']?->type === Agreement::TYPE_ADDENDUM ? 'Addendum' : 'MOU';
        session()->flash('message', "Dokumen {$docType} bertanda tangan berhasil diunggah dan sedang menunggu verifikasi admin.");
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

    public function toggleTicketStatus($id)
    {
        $harga = $this->authorizedHarga($id);
        $harga->status = $harga->status === 'active' ? 'inactive' : 'active';
        $harga->save();
        session()->flash('message', 'Status tiket berhasil diperbarui.');
    }

    public function confirmDeleteTicket($id)
    {
        $harga = $this->authorizedHarga($id);
        $hasTransactions = $this->hargaHasTransactions($harga);

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
            $harga = $this->authorizedHarga($this->deletingHargaId);
            if ($this->hargaHasTransactions($harga)) {
                $this->dispatch('close-modal', name: 'delete-ticket-modal');
                $this->deletingHargaId = null;
                session()->flash('error', 'Tiket tidak dapat dihapus karena sudah memiliki transaksi.');

                return;
            }

            $harga->delete();
            $this->dispatch('close-modal', name: 'delete-ticket-modal');
            $this->deletingHargaId = null;
            session()->flash('message', 'Tiket berhasil dihapus.');
        }
    }

    public function openAddTicketModal()
    {
        $this->resetValidation();
        $this->newHarga = [
            'kategori' => '',
            'qty' => 0,
            'harga' => 0,
        ];

        $this->dispatch('open-modal', name: 'add-ticket-modal');
    }

    public function addTicket()
    {
        $this->getEventData();

        $validated = $this->validate([
            'newHarga.kategori' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hargas', 'kategori')
                    ->where(fn($query) => $query->where('uid', $this->eventUid)),
            ],
            'newHarga.qty' => 'required|integer|min:0',
            'newHarga.harga' => 'required|integer|min:0',
        ], [
            'newHarga.kategori.unique' => 'Nama kategori tiket sudah digunakan pada event ini.',
        ]);

        Harga::create([
            'uid' => $this->eventUid,
            'kategori' => $validated['newHarga']['kategori'],
            'qty' => (int) $validated['newHarga']['qty'],
            'harga' => (int) $validated['newHarga']['harga'],
            'status' => 'active',
        ]);

        $this->dispatch('close-modal', name: 'add-ticket-modal');
        session()->flash('message', 'Tiket berhasil ditambahkan.');
    }

    public function editTicket($id)
    {
        $harga = $this->authorizedHarga($id);
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
            'editingHarga.kategori' => 'required',
            'editingHarga.qty' => 'required|integer|min:0',
            'editingHarga.harga' => 'required|integer|min:0',
        ]);

        $harga = $this->authorizedHarga($this->editingHargaId);
        $minimumQty = $this->minimumLockedQty($harga);
        if ((int) $this->editingHarga['qty'] < $minimumQty) {
            $this->addError('editingHarga.qty', 'Qty tiket tidak boleh lebih kecil dari jumlah tiket yang sudah terjual atau sedang dipesan.');
            session()->flash('error', 'Qty tiket tidak boleh lebih kecil dari jumlah tiket yang sudah terjual atau sedang dipesan.');

            return;
        }

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
            session()->flash('error', 'Transaksi tidak ditemukan atau bukan milik event ini.');

            return;
        }

        $this->selectedTransactionId = $uid;
        $this->dispatch('open-modal', name: 'transaction-detail-modal');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filterPayment', 'filterRange', 'searchTransaction', 'perPage', 'activeTab'])) {
            $this->sanitizeFilters();
            $this->resetPage();
        }
    }

    public function render()
    {
        $this->sanitizeFilters();

        $event = $this->getEventData();
        $metrics = $this->getMetricsData();

        $transactions = [];
        if ($this->activeTab === 'transaksi') {
            $transactions = $this->authorizedTransactionQuery()
                ->with(['users', 'cashBuyer'])
                ->withSum(['hargaCarts as total_qty' => function ($q) {
                    $q->whereNull('deleted_at');
                }], 'quantity')
                ->where('status', 'SUCCESS');

            $transactions = $this->applyFilters($transactions)
                ->orderBy('created_at', 'desc')
                ->paginate($this->perPage);
        }

        $mouPreview = null;
        $addendumPreview = null;
        $mouAgreement = null;
        $activeAgreement = null;
        $agreementsHistory = [];
        $mouUnsignedAvailable = false;
        $mouSignedAvailable = false;
        $mouUploadAvailable = false;
        $mouSignedReviewStatus = null;
        $mouSignedAwaitingReview = false;
        $mouSignedRejected = false;
        $mouCompleted = false;

        if ($this->activeTab === 'mou') {
            $hasAgreementsTable = \Illuminate\Support\Facades\Schema::hasTable('agreements');

            $agreementsHistory = $hasAgreementsTable
                ? $event->agreements()
                    ->orderByRaw("CASE WHEN type = 'mou' THEN 1 ELSE 2 END ASC")
                    ->orderBy('version', 'asc')
                    ->get()
                : collect();

            $activeAgreement = $hasAgreementsTable
                ? ($event->agreements()
                    ->whereNotIn('status', [Agreement::STATUS_COMPLETED, Agreement::STATUS_CANCELLED])
                    ->latest('id')
                    ->first()
                    ?? $event->latestCompletedAgreement()
                    ?? $event->currentMouAgreement)
                : null;

            $mouAgreement = $activeAgreement ?? $event->currentMouAgreement;

            if ($activeAgreement?->isAddendum()) {
                $addendumPreview = app(\App\Services\Agreements\AgreementVersioningService::class)
                    ->buildAddendumPreview($event, $activeAgreement);
            } else {
                $mouPreview = app(AgreementPreviewService::class)->buildForEvent($event);
            }

            $mouUnsignedAvailable = $mouAgreement !== null
                && ($mouAgreement->isReady() || $mouAgreement->isCompleted())
                && filled($mouAgreement->unsigned_pdf_path);
            $mouSignedReviewStatus = $mouAgreement?->signed_review_status;
            $mouSignedAvailable = $mouAgreement !== null
                && ($mouAgreement->isReady() || $mouAgreement->isCompleted())
                && filled($mouAgreement->signed_pdf_path);
            $mouUploadAvailable = $mouAgreement !== null
                && $mouAgreement->isReady();
            $mouSignedAwaitingReview = $mouAgreement !== null
                && $mouAgreement->isReady()
                && $mouSignedAvailable
                && in_array($mouSignedReviewStatus, [null, Agreement::SIGNED_REVIEW_PENDING], true);
            $mouSignedRejected = $mouAgreement !== null
                && $mouAgreement->isReady()
                && $mouSignedReviewStatus === Agreement::SIGNED_REVIEW_REJECTED;
            $mouCompleted = $mouAgreement !== null
                && $mouAgreement->isCompleted();
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
                $discount = $selectedTransaction->hargaCarts->sum(fn($i) => (int) ($i->disc ?? 0));
            }
        }

        return view('livewire.dashboard.event-detail', [
            'event' => $event,
            'metrics' => $metrics,
            'transactions' => $transactions,
            'selectedTransaction' => $selectedTransaction,
            'discount' => $discount,
            'voucherCode' => $voucherCode,
            'mouPreview' => $mouPreview,
            'addendumPreview' => $addendumPreview,
            'agreementsHistory' => $agreementsHistory,
            'activeAgreement' => $activeAgreement,
            'mouAgreement' => $mouAgreement,
            'mouUnsignedAvailable' => $mouUnsignedAvailable,
            'mouSignedAvailable' => $mouSignedAvailable,
            'mouUploadAvailable' => $mouUploadAvailable,
            'mouSignedReviewStatus' => $mouSignedReviewStatus,
            'mouSignedAwaitingReview' => $mouSignedAwaitingReview,
            'mouSignedRejected' => $mouSignedRejected,
            'mouCompleted' => $mouCompleted,
        ]);
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
            session()->flash('error', 'Transaksi tidak ditemukan atau bukan milik event ini.');

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
                    if (! sendEmailETransaksi::resolveRecipient($user, $cart)) {
                        session()->flash('error', 'Email penerima tiket tidak valid atau kosong.');

                        return;
                    }

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
            session()->flash('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

    public function addTalent()
    {
        $this->getEventData();

        if ($this->editingTalentId) {
            return $this->updateTalent();
        }

        $this->validate([
            'talentName' => 'required|string|max:255',
            'talentImage' => SecureImageStorage::rules(true),
            'talentLink' => 'nullable|url',
        ]);

        $imageName = app(SecureImageStorage::class)->storeBasename($this->talentImage, 'talent');

        $talent = Talent::create([
            'uid' => $this->eventUid,
            'talent' => $this->talentName,
            'gambar' => $imageName,
            'link' => $this->talentLink,
        ]);

        $this->reset(['talentName', 'talentLink', 'talentImage', 'editingTalentId', 'existingTalentImage']);
        $this->dispatch('close-modal', name: 'add-talent-modal');

        session()->flash('success', 'Talent berhasil ditambahkan!');
    }

    public function openAddTalentModal()
    {
        $this->reset(['talentName', 'talentLink', 'talentImage', 'editingTalentId', 'existingTalentImage']);
        $this->dispatch('open-modal', name: 'add-talent-modal');
    }

    public function editTalent($id)
    {
        $this->reset(['talentName', 'talentLink', 'talentImage', 'editingTalentId', 'existingTalentImage']);
        $this->editingTalentId = $id; // Set this first to show loading state if needed

        $talent = $this->authorizedTalent($id);
        $this->talentName = $talent->talent;
        $this->talentLink = $talent->link;
        $this->existingTalentImage = $talent->gambar;

        $this->dispatch('open-modal', name: 'add-talent-modal');
    }

    public function updateTalent()
    {
        $this->validate([
            'talentName' => 'required|string|max:255',
            'talentImage' => SecureImageStorage::rules(),
            'talentLink' => 'nullable|url',
        ]);

        $talent = $this->authorizedTalent($this->editingTalentId);

        $data = [
            'talent' => $this->talentName,
            'link' => $this->talentLink,
        ];

        $oldImage = null;
        if ($this->talentImage) {
            $oldImage = $talent->gambar;
            $data['gambar'] = app(SecureImageStorage::class)
                ->storeBasename($this->talentImage, 'talent');
        }

        $talent->update($data);
        app(SecureImageStorage::class)->delete('talent', $oldImage);

        $this->reset(['talentName', 'talentLink', 'talentImage', 'editingTalentId', 'existingTalentImage']);
        $this->dispatch('close-modal', name: 'add-talent-modal');

        session()->flash('success', 'Talent berhasil diperbarui!');
    }

    public function confirmDeleteTalent($id)
    {
        $this->authorizedTalent($id);
        $this->deletingTalentId = $id;
        $this->dispatch('open-modal', name: 'delete-talent-modal');
    }

    public function deleteTalent()
    {
        $talent = $this->authorizedTalent($this->deletingTalentId);

        app(SecureImageStorage::class)->delete('talent', $talent->gambar);
        $talent->delete();
        $this->dispatch('close-modal', name: 'delete-talent-modal');
        $this->deletingTalentId = null;
        session()->flash('success', 'Talent berhasil dihapus!');
    }

    private function authorizedHarga($id): Harga
    {
        $this->getEventData();

        return Harga::where('id', $id)
            ->where('uid', $this->eventUid)
            ->firstOrFail();
    }

    private function authorizedTalent($id): Talent
    {
        $this->getEventData();

        return Talent::where('id', $id)
            ->where('uid', $this->eventUid)
            ->firstOrFail();
    }

    private function hargaHasTransactions(Harga $harga): bool
    {
        return $harga->hargaCarts()
            ->whereHas('cart', fn($query) => $query->whereIn('status', self::BLOCKING_TRANSACTION_STATUSES))
            ->exists();
    }

    private function minimumLockedQty(Harga $harga): int
    {
        $cartQuantity = (int) $harga->hargaCarts()
            ->whereHas('cart', fn($query) => $query->whereIn('status', self::LOCKED_QTY_STATUSES))
            ->sum('quantity');

        return max((int) $harga->sold_qty + (int) $harga->reserved_qty, $cartQuantity);
    }
}
