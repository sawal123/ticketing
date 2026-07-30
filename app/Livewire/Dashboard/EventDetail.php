<?php

namespace App\Livewire\Dashboard;

use App\Jobs\sendEmailETransaksi;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Talent;
use App\Models\Voucher;
use App\Services\SecureImageStorage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EventDetail extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Layout('layouts.unified')]
    public $eventUid;

    public $activeTab = 'umum'; // umum, tiket, transaksi

    public $searchTransaction = '';

    public $perPage = 10;

    public $showFullDescription = false;

    // Filters for Transactions
    public $filterPayment = 'all'; // all, cash, non-cash

    public $filterRange; // Format: "YYYY-MM-DD to YYYY-MM-DD"

    // For Add/Edit Ticket Modal
    public $newHarga = [
        'kategori' => '',
        'qty' => 0,
        'harga' => 0,
        'status' => 'active',
    ];

    public $editingHargaId;

    public $editingHarga = [
        'kategori' => '',
        'qty' => 0,
        'harga' => 0,
        'status' => 'active',
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
        $query = Cart::where('event_uid', $this->eventUid)->where('status', 'SUCCESS');
        $query = $this->applyFilters($query);

        $transactionIds = (clone $query)->distinct()->pluck('uid');
        $totalTransactions = $transactionIds->count();

        $hargaCarts = HargaCart::whereIn('uid', $transactionIds)->get();

        $grossRevenue = $hargaCarts->sum(fn ($item) => $item->quantity * $item->harga_ticket);
        $totalTicketsSold = $hargaCarts->sum('quantity');

        $totalPajak = (clone $query)->sum('pajak');
        $totalInternetFee = (clone $query)->sum('internet_fee');

        // Calculate Total Discount based on HargaCart to sync with UI "Terpakai" count
        $totalDiscount = 0;

        $hargaCartsWithVoucher = $hargaCarts->whereNotNull('voucher');

        foreach ($hargaCartsWithVoucher as $hc) {
            $voucher = Voucher::where('code', $hc->voucher)
                ->where('event_uid', $this->eventUid)
                ->first();

            if ($voucher) {
                $itemTotal = $hc->quantity * $hc->harga_ticket;
                $discountValue = 0;

                if (strtolower($voucher->unit) === 'rupiah') {
                    $discountValue = $voucher->nominal;
                } elseif (strtolower($voucher->unit) === 'persen' || $voucher->unit === '%') {
                    $discountValue = ($voucher->nominal / 100) * $itemTotal;
                    if ($voucher->max_disc > 0 && $discountValue > $voucher->max_disc) {
                        $discountValue = $voucher->max_disc;
                    }
                }

                $totalDiscount += $discountValue;
            }
        }

        $event = $this->getEventData();
        $feePercent = $event->fee ?? 0;

        // Calculate Total Pajak based on net revenue (after discount)
        // to match the Dashboard Omset formula: (Gross - Discount) * (1 + Fee%)
        $totalPajak = ($grossRevenue - $totalDiscount) * ($feePercent / 100);
        $totalInternetFee = (clone $query)->sum('internet_fee');

        return [
            'total_transactions' => $totalTransactions,
            'total_revenue' => $grossRevenue,
            'total_tickets' => $totalTicketsSold,
            'total_pajak' => $totalPajak,
            'total_internet_fee' => $totalInternetFee,
            'total_discount' => $totalDiscount,
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
        return $query->when($this->filterPayment !== 'all', function ($q) {
            if ($this->filterPayment === 'cash') {
                $q->where('payment_type', 'cash');
            } else {
                $q->where('payment_type', '!=', 'cash');
            }
        })
            ->when($this->filterRange, function ($q) {
                $dates = explode(' to ', $this->filterRange);
                if (count($dates) === 2) {
                    $q->whereBetween('carts.created_at', [
                        Carbon::parse($dates[0])->startOfDay(),
                        Carbon::parse($dates[1])->endOfDay(),
                    ]);
                } else {
                    $q->whereDate('carts.created_at', Carbon::parse($dates[0]));
                }
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

    /**
     * Optimized query for exports (Excel/PDF)
     * Avoids N+1 and minimizes object hydration
     */
    protected function getExportQuery()
    {
        $query = DB::table('carts')
            ->join('users', 'users.uid', '=', 'carts.user_uid')
            ->leftJoin('cashes', 'cashes.uid', '=', 'carts.uid')
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->select([
                'carts.created_at',
                'carts.invoice',
                DB::raw("CASE WHEN carts.payment_type = 'cash' THEN COALESCE(cashes.name, 'Data Pembeli Tidak Ditemukan') ELSE users.name END as buyer_name"),
                DB::raw("CASE WHEN carts.payment_type = 'cash' THEN COALESCE(cashes.email, '-') ELSE users.email END as buyer_email"),
                'harga_carts.kategori_harga',
                'harga_carts.quantity',
                'harga_carts.harga_ticket',
                'carts.payment_type',
                'carts.konfirmasi',
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

        if ($this->filterRange) {
            $dates = explode(' to ', $this->filterRange);
            if (count($dates) === 2) {
                $query->whereBetween('carts.created_at', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay(),
                ]);
            } else {
                $query->whereDate('carts.created_at', Carbon::parse($dates[0]));
            }
        }

        if ($this->searchTransaction) {
            $query->where(function ($q) {
                $q->where('carts.invoice', 'like', '%'.$this->searchTransaction.'%')
                    ->orWhere(function ($online) {
                        $online->where('carts.payment_type', '!=', 'cash')
                            ->where(function ($user) {
                                $user->where('users.name', 'like', '%'.$this->searchTransaction.'%')
                                    ->orWhere('users.email', 'like', '%'.$this->searchTransaction.'%');
                            });
                    })
                    ->orWhere(function ($cash) {
                        $cash->where('carts.payment_type', 'cash')
                            ->where(function ($cashBuyer) {
                                $cashBuyer->where('cashes.name', 'like', '%'.$this->searchTransaction.'%')
                                    ->orWhere('cashes.email', 'like', '%'.$this->searchTransaction.'%');
                            });
                    });
            });
        }

        return $query->orderBy('carts.created_at', 'desc');
    }

    public function exportExcel()
    {
        $fileName = 'transaksi-event-'.Str::slug($this->getEventData()->event).'-'.now()->format('YmdHis').'.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Header Row
            fputcsv($file, ['Tanggal', 'Invoice', 'Nama Pembeli', 'Email', 'Kategori Tiket', 'Qty', 'Harga Satuan', 'Total', 'Status Kehadiran']);

            // Data Rows (Optimized with cursor)
            $this->getExportQuery()->cursor()->each(function ($row) use ($file) {
                fputcsv($file, [
                    $row->created_at,
                    $row->invoice,
                    $row->buyer_name,
                    $row->buyer_email,
                    $row->kategori_harga,
                    $row->quantity,
                    $row->harga_ticket,
                    ($row->quantity * $row->harga_ticket),
                    $row->konfirmasi == '1' ? 'Hadir' : 'Belum Hadir',
                ]);
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf()
    {
        $event = $this->getEventData();
        $transactions = $this->getExportQuery()->get();

        $filter_info = 'Semua Data';
        if ($this->filterPayment !== 'all' || $this->filterRange || $this->searchTransaction) {
            $parts = [];
            if ($this->filterPayment !== 'all') {
                $parts[] = 'Metode: '.strtoupper($this->filterPayment);
            }
            if ($this->filterRange) {
                $parts[] = 'Rentang: '.$this->filterRange;
            }
            if ($this->searchTransaction) {
                $parts[] = "Cari: '".$this->searchTransaction."'";
            }
            $filter_info = implode(', ', $parts);
        }

        $html = view('exports.transactions-pdf', [
            'event' => $event,
            'transactions' => $transactions,
            'filter_info' => $filter_info,
        ])->render();

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, 'transaksi-event-'.Str::slug($event->event).'.html');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
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

    public function openAddTicketModal()
    {
        $this->resetValidation();
        $this->newHarga = [
            'kategori' => '',
            'qty' => 0,
            'harga' => 0,
            'status' => 'active',
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
                    ->where(fn ($query) => $query->where('uid', $this->eventUid)),
            ],
            'newHarga.qty' => 'required|integer|min:0',
            'newHarga.harga' => 'required|numeric|min:0',
            'newHarga.status' => 'required|in:active,inactive',
        ], [
            'newHarga.kategori.unique' => 'Nama kategori tiket sudah digunakan pada event ini.',
        ]);

        Harga::create([
            'uid' => $this->eventUid,
            ...$validated['newHarga'],
        ]);

        $this->dispatch('close-modal', name: 'add-ticket-modal');
        session()->flash('message', 'Tiket berhasil ditambahkan.');
    }

    public function editTicket($id)
    {
        $harga = Harga::findOrFail($id);
        $this->editingHargaId = $id;
        $this->editingHarga = [
            'kategori' => $harga->kategori,
            'qty' => $harga->qty,
            'harga' => $harga->harga,
            'status' => $harga->status,
        ];

        $this->dispatch('open-modal', name: 'edit-ticket-modal');
    }

    public function updateTicket()
    {
        $this->validate([
            'editingHarga.kategori' => 'required',
            'editingHarga.qty' => 'required|numeric',
            'editingHarga.harga' => 'required|numeric',
        ]);

        $harga = Harga::findOrFail($this->editingHargaId);
        $harga->update($this->editingHarga);

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
        if (in_array($propertyName, ['filterPayment', 'filterRange', 'searchTransaction'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
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
                    $voucher = Voucher::where('code', $voucherCode)
                        ->where('event_uid', $this->eventUid)
                        ->first();
                    if ($voucher) {
                        $totalTickets = $selectedTransaction->hargaCarts->sum(fn ($i) => $i->quantity * $i->harga_ticket);
                        if (strtolower($voucher->unit) === 'rupiah') {
                            $discount = $voucher->nominal;
                        } elseif (strtolower($voucher->unit) === 'persen' || $voucher->unit === '%') {
                            $discount = ($voucher->nominal / 100) * $totalTickets;
                            if ($voucher->max_disc > 0 && $discount > $voucher->max_disc) {
                                $discount = $voucher->max_disc;
                            }
                        }
                    }
                }
            }
        }

        return view('livewire.dashboard.event-detail', [
            'event' => $event,
            'metrics' => $metrics,
            'transactions' => $transactions,
            'selectedTransaction' => $selectedTransaction,
            'discount' => $discount,
            'voucherCode' => $voucherCode,
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

        try {
            if ($cart->payment_type === 'cash') {
                $cash = $cart->cashBuyer;
                if ($cash) {
                    if (! filter_var($cash->email, FILTER_VALIDATE_EMAIL)) {
                        session()->flash('error', 'Email pembeli cash tidak valid atau kosong.');

                        return;
                    }

                    dispatch(new sendEmailTrnsaksi($cash->email, $cash->name, $cart->uid));
                } else {
                    session()->flash('error', 'Data pembeli tunai tidak ditemukan.');

                    return;
                }
            } else {
                $user = $cart->users;
                if ($user) {
                    dispatch(new sendEmailETransaksi($user, $cart));
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

    public function addTalent()
    {
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

        $talent = Talent::findOrFail($id);
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

        $talent = Talent::findOrFail($this->editingTalentId);

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
        $this->deletingTalentId = $id;
        $this->dispatch('open-modal', name: 'delete-talent-modal');
    }

    public function deleteTalent()
    {
        $talent = Talent::findOrFail($this->deletingTalentId);

        app(SecureImageStorage::class)->delete('talent', $talent->gambar);
        $talent->delete();
        $this->dispatch('close-modal', name: 'delete-talent-modal');
        $this->deletingTalentId = null;
        session()->flash('success', 'Talent berhasil dihapus!');
    }
}
