<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use App\Models\Cart;
use App\Models\Harga;
use App\Models\HargaCart;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EventDetail extends Component
{
    use WithPagination;

    #[Layout('layouts.unified')]

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
        'status' => 'active'
    ];

    // For Delete Modal
    public $deletingHargaId;

    // For Transaction Detail Modal
    public $selectedTransactionId;

    // For Resend Email Confirmation
    public $resendEmailUid;

    protected $queryString = [
        'activeTab' => ['except' => 'umum'],
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
        $ownerId = auth()->user()->id;
        $isAdmin = auth()->user()->role === 'admin';
        
        $query = Event::with([
            'talents', 
            'hargas' => function($query) {
                $query->withCount(['hargaCarts as sold_count' => function($q) {
                    $q->whereHas('cart', function($c) {
                        $c->where('status', 'SUCCESS');
                    });
                }]);
            }
        ])->where('uid', $this->eventUid);
        
        if (!$isAdmin) {
            $query->where('user_uid', auth()->user()->uid);
        }

        return $query->firstOrFail();
    }

    protected function getMetricsData()
    {
        $query = Cart::where('event_uid', $this->eventUid)->where('status', 'SUCCESS');
        $query = $this->applyFilters($query);

        $transactionIds = $query->pluck('uid');
        $totalTransactions = $transactionIds->count();
        
        $hargaCarts = HargaCart::whereIn('uid', $transactionIds)->get();
        
        $totalRevenue = $hargaCarts->sum(fn($item) => $item->quantity * $item->harga_ticket);
        $totalTicketsSold = $hargaCarts->sum('quantity');
        
        $totalPajak = $query->sum('pajak');
        $totalInternetFee = $query->sum('internet_fee');

        return [
            'total_transactions' => $totalTransactions,
            'total_revenue' => $totalRevenue,
            'total_tickets' => $totalTicketsSold,
            'total_pajak' => $totalPajak,
            'total_internet_fee' => $totalInternetFee,
        ];
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
                    $q->whereBetween('created_at', [
                        Carbon::parse($dates[0])->startOfDay(),
                        Carbon::parse($dates[1])->endOfDay()
                    ]);
                } else {
                    $q->whereDate('created_at', Carbon::parse($dates[0]));
                }
            })
            ->when($this->searchTransaction, function ($q) {
                $q->where(function($sub) {
                    $sub->where('invoice', 'like', '%' . $this->searchTransaction . '%')
                      ->orWhereHas('users', function ($u) {
                          $u->where('name', 'like', '%' . $this->searchTransaction . '%');
                      });
                });
            });
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
        $this->showFullDescription = !$this->showFullDescription;
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
        $hasTransactions = $harga->hargaCarts()->whereHas('cart', function($q) {
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
            'status' => $harga->status
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
            $transactions = Cart::query()
                ->with(['users'])
                ->where('event_uid', $this->eventUid)
                ->where('status', 'SUCCESS');
            
            $transactions = $this->applyFilters($transactions)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        $selectedTransaction = null;
        $discount = 0;
        $voucherCode = null;
        
        if ($this->selectedTransactionId) {
            $selectedTransaction = Cart::with(['users', 'hargaCarts.masterHarga'])->where('uid', $this->selectedTransactionId)->first();
            
            if ($selectedTransaction) {
                $cartVoucher = \App\Models\CartVoucher::where('uid', $this->selectedTransactionId)->first();
                if ($cartVoucher) {
                    $voucherCode = $cartVoucher->code;
                    $voucher = \App\Models\Voucher::where('code', $voucherCode)->first();
                    if ($voucher) {
                        $totalTickets = $selectedTransaction->hargaCarts->sum(fn($i) => $i->quantity * $i->harga_ticket);
                        if ($voucher->unit === 'rupiah') {
                            $discount = $voucher->nominal;
                        } elseif ($voucher->unit === 'persen') {
                            $discount = ($voucher->nominal / 100) * $totalTickets;
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
            'voucherCode' => $voucherCode
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
        if (!$uid) return;

        $cart = Cart::where('uid', $uid)->first();
        if (!$cart) return;

        if ($cart->status !== 'SUCCESS') {
            session()->flash('error', 'Email hanya dapat dikirim ulang untuk transaksi yang sukses.');
            return;
        }

        $barcode = $cart->invoice;

        try {
            if ($cart->payment_type === 'cash') {
                $cash = \App\Models\Cash::where('uid', $uid)->first();
                if ($cash) {
                    dispatch(new \App\Jobs\sendEmailTrnsaksi($cash->email, $cash->name, $barcode));
                } else {
                     session()->flash('error', 'Data pembeli tunai tidak ditemukan.');
                     return;
                }
            } else {
                $user = \App\Models\User::where('uid', $cart->user_uid)->first();
                if ($user) {
                    dispatch(new \App\Jobs\sendEmailETransaksi($user, $cart, $barcode));
                } else {
                    session()->flash('error', 'Data pembeli tidak ditemukan.');
                    return;
                }
            }

            $this->dispatch('close-modal', name: 'resend-email-modal');
            $this->resendEmailUid = null;
            session()->flash('message', 'Barcode tiket berhasil dikirim ulang ke email pembeli.');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

}
