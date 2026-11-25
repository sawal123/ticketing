<?php

namespace App\Livewire\Dashboard;

use App\Models\Cart;
use App\Models\Event;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class PartnerIndex extends Component
{
    use WithPagination;

    #[Layout('layouts.unified')]
    public $search = '';

    public $event_uid = '';

    // Form properties
    public $partner_id;

    public $name;

    public $email;

    public $city;

    public $alamat;

    public $hp;

    public $selected_event_uid;

    public $status = 'active';

    public $discount_type = '';

    public $discount_value;

    public $discount_max;

    public $allow_promo_tickets = false;

    public $commission_type = '';

    public $commission_value;

    // Stats
    public $totalPartners = 0;

    public $activePartners = 0;

    public $totalCities = 0;

    public $totalPartnerCommission = 0;

    public $isEditMode = false;

    // Transactions modal
    public $selectedPartnerName = '';

    public $selectedPartnerReferensi = '';

    public $partnerTransactions = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'city' => 'required|string|max:100',
        'alamat' => 'required|string',
        'hp' => 'required|string|max:20',
        'discount_type' => 'nullable|in:,rupiah,persen',
        'discount_value' => 'nullable|integer|min:0',
        'discount_max' => 'nullable|integer|min:0',
        'allow_promo_tickets' => 'nullable|boolean',
        'commission_type' => 'nullable|in:,rupiah,persen',
        'commission_value' => 'nullable|integer|min:0',
    ];

    public function resetForm()
    {
        $this->reset([
            'partner_id',
            'name',
            'email',
            'city',
            'alamat',
            'hp',
            'status',
            'selected_event_uid',
            'discount_type',
            'discount_value',
            'discount_max',
            'allow_promo_tickets',
            'commission_type',
            'commission_value',
            'isEditMode',
        ]);
        $this->status = 'active';
        $this->discount_type = '';
        $this->commission_type = '';
        $this->allow_promo_tickets = false;
    }

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isEditMode = false;
        $this->dispatch('open-modal', name: 'partner-modal');
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isEditMode = true;
        $partner = $this->ownedPartnerQuery($id)->firstOrFail();

        $this->partner_id = $partner->id;
        $this->name = $partner->name;
        $this->email = $partner->email;
        $this->city = $partner->city;
        $this->alamat = $partner->alamat;
        $this->hp = $partner->hp;
        $this->status = $partner->status;
        $this->selected_event_uid = $partner->event_uid;
        $this->discount_type = (string) ($partner->discount_type ?? '');
        $this->discount_value = $partner->discount_value;
        $this->discount_max = $partner->discount_max;
        $this->allow_promo_tickets = (bool) ($partner->allow_promo_tickets ?? false);
        $this->commission_type = (string) ($partner->commission_type ?? '');
        $this->commission_value = $partner->commission_value;

        $this->dispatch('open-modal', name: 'partner-modal');
    }

    public function save()
    {
        $this->validate();

        $discountType = trim((string) $this->discount_type);
        $commissionType = trim((string) $this->commission_type);

        if ($discountType !== '') {
            $discountValue = filter_var($this->discount_value, FILTER_VALIDATE_INT);
            if ($discountValue === false || $discountValue < 1) {
                $this->addError('discount_value', 'Nominal potongan khusus partner wajib minimal 1.');

                return;
            }
            if ($discountType === Partner::TYPE_PERSEN && $discountValue > 99) {
                $this->addError('discount_value', 'Potongan persen partner maksimal 99%.');

                return;
            }
        }

        if ($commissionType !== '') {
            $commissionValue = filter_var($this->commission_value, FILTER_VALIDATE_INT);
            if ($commissionValue === false || $commissionValue < 1) {
                $this->addError('commission_value', 'Nominal komisi partner wajib minimal 1.');

                return;
            }
            if ($commissionType === Partner::TYPE_PERSEN && $commissionValue > 100) {
                $this->addError('commission_value', 'Komisi persen partner maksimal 100%.');

                return;
            }
        }

        if ($this->selected_event_uid) {
            $this->ownedEventQuery($this->selected_event_uid)->firstOrFail();
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'city' => $this->city,
            'alamat' => $this->alamat,
            'hp' => $this->hp,
            'status' => $this->status,
            'event_uid' => $this->selected_event_uid ?: null,
        ];

        if (Schema::hasColumn('partners', 'discount_type')) {
            $data['discount_type'] = $discountType !== '' ? $discountType : null;
            $data['discount_value'] = $discountType !== '' ? (int) $this->discount_value : null;
            $data['discount_max'] = ($discountType === Partner::TYPE_PERSEN && filled($this->discount_max) && (int) $this->discount_max > 0)
                ? (int) $this->discount_max
                : null;
            $data['allow_promo_tickets'] = $discountType !== '' && (bool) $this->allow_promo_tickets;
            $data['commission_type'] = $commissionType !== '' ? $commissionType : null;
            $data['commission_value'] = $commissionType !== '' ? (int) $this->commission_value : null;
        }

        if ($this->isEditMode) {
            $this->ownedPartnerQuery($this->partner_id)->firstOrFail()->update($data);
            session()->flash('success', 'Partner berhasil diperbarui.');
        } else {
            $data['uid'] = (string) Str::uuid();
            $data['user_uid'] = auth()->user()->role === 'staff' ? auth()->user()->parent_uid : auth()->user()->uid;
            $data['referensi'] = 'PARTNER-'.strtoupper(Str::random(6));
            Partner::create($data);
            session()->flash('success', 'Partner berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'partner-modal');
        $this->resetForm();
    }

    public function openTransactionsModal($id)
    {
        $partner = $this->ownedPartnerQuery($id)->firstOrFail();
        $this->selectedPartnerName = $partner->name;
        $this->selectedPartnerReferensi = $partner->referensi;

        if (! Schema::hasColumn('carts', 'partner_uid')) {
            $this->partnerTransactions = [];
            $this->dispatch('open-modal', name: 'partner-transactions-modal');

            return;
        }

        $this->partnerTransactions = Cart::query()
            ->with(['users', 'event', 'hargaCarts'])
            ->where('partner_uid', $partner->uid)
            ->where('status', Cart::STATUS_SUCCESS)
            ->latest('paid_at')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(fn (Cart $cart) => [
                'invoice' => $cart->invoice ?? '-',
                'event_name' => $cart->event->event ?? '-',
                'buyer_name' => $cart->buyerName() ?: ($cart->users->name ?? 'Guest'),
                'buyer_email' => $cart->buyerEmail() ?: ($cart->users->email ?? '-'),
                'qty' => (int) $cart->hargaCarts->sum('quantity'),
                'gross_amount' => (int) ($cart->gross_amount ?? 0),
                'partner_discount_amount' => (int) ($cart->partner_discount_amount ?? 0),
                'partner_commission_amount' => (int) ($cart->partner_commission_amount ?? 0),
                'paid_at' => $cart->paid_at ? $cart->paid_at->format('d M Y, H:i') : ($cart->created_at?->format('d M Y, H:i') ?? '-'),
            ])
            ->all();

        $this->dispatch('open-modal', name: 'partner-transactions-modal');
    }

    public function toggleStatus($id)
    {
        $partner = $this->ownedPartnerQuery($id)->firstOrFail();
        $partner->status = $partner->status === 'active' ? 'inactive' : 'active';
        $partner->save();
        session()->flash('success', 'Status partner berhasil diubah.');
    }

    public function confirmDelete($id)
    {
        $this->ownedPartnerQuery($id)->firstOrFail();
        $this->partner_id = $id;
        $this->dispatch('open-modal', name: 'delete-modal');
    }

    public function delete()
    {
        $this->ownedPartnerQuery($this->partner_id)->firstOrFail()->delete();
        $this->dispatch('close-modal', name: 'delete-modal');
        session()->flash('success', 'Partner berhasil dihapus.');
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->role === 'admin';
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // Base Query for Table
        $query = Partner::query()->with('event');

        if (! $isAdmin) {
            $query->where('user_uid', $ownerId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('referensi', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->event_uid) {
            $query->where('event_uid', $this->event_uid);
        }

        $partners = $query->latest()->paginate(10);

        // Stats Calculation
        $statsQuery = Partner::query();
        if (! $isAdmin) {
            $statsQuery->where('user_uid', $ownerId);
        }
        $this->totalPartners = (clone $statsQuery)->count();
        $this->activePartners = (clone $statsQuery)->where('status', 'active')->count();
        $this->totalCities = (clone $statsQuery)->distinct('city')->count();

        $partnerStats = [];
        $this->totalPartnerCommission = 0;

        if (Schema::hasTable('carts') && Schema::hasColumn('carts', 'partner_uid')) {
            $allOwnedPartnerUids = (clone $statsQuery)->pluck('uid')->all();

            if ($allOwnedPartnerUids !== []) {
                $this->totalPartnerCommission = (int) Cart::query()
                    ->whereIn('partner_uid', $allOwnedPartnerUids)
                    ->where('status', Cart::STATUS_SUCCESS)
                    ->sum('partner_commission_amount');
            }

            $pagePartnerUids = $partners->getCollection()->pluck('uid')->filter()->values()->all();
            if ($pagePartnerUids !== []) {
                $qtySubquery = DB::table('harga_carts')
                    ->select('uid', DB::raw('SUM(quantity) as ticket_qty'))
                    ->whereNull('deleted_at')
                    ->groupBy('uid');

                $rows = DB::table('carts')
                    ->leftJoinSub($qtySubquery, 'hc', 'hc.uid', '=', 'carts.uid')
                    ->whereIn('carts.partner_uid', $pagePartnerUids)
                    ->where('carts.status', Cart::STATUS_SUCCESS)
                    ->whereNull('carts.deleted_at')
                    ->groupBy('carts.partner_uid')
                    ->select([
                        'carts.partner_uid',
                        DB::raw('COUNT(carts.id) as tx_count'),
                        DB::raw('COALESCE(SUM(hc.ticket_qty), 0) as ticket_qty'),
                        DB::raw('COALESCE(SUM(carts.gross_amount), 0) as total_omzet'),
                        DB::raw('COALESCE(SUM(carts.partner_discount_amount), 0) as total_discount'),
                        DB::raw('COALESCE(SUM(carts.partner_commission_amount), 0) as total_commission'),
                    ])
                    ->get();

                foreach ($rows as $row) {
                    $partnerStats[$row->partner_uid] = [
                        'tx_count' => (int) $row->tx_count,
                        'ticket_qty' => (int) $row->ticket_qty,
                        'total_omzet' => (int) $row->total_omzet,
                        'total_discount' => (int) $row->total_discount,
                        'total_commission' => (int) $row->total_commission,
                    ];
                }
            }
        }

        // Events for dropdown
        $eventsQuery = Event::query();
        if (! $isAdmin) {
            $eventsQuery->where('user_uid', $ownerId);
        }
        $events = $eventsQuery->orderBy('event', 'asc')->get();

        return view('livewire.dashboard.partner-index', [
            'partners' => $partners,
            'events' => $events,
            'partnerStats' => $partnerStats,
        ]);
    }

    private function ownedPartnerQuery($id)
    {
        $user = auth()->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        return Partner::where('id', $id)
            ->when($user->role !== 'admin', fn ($query) => $query->where('user_uid', $ownerId));
    }

    private function ownedEventQuery(string $uid)
    {
        $user = auth()->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        return Event::where('uid', $uid)
            ->when($user->role !== 'admin', fn ($query) => $query->where('user_uid', $ownerId));
    }
}
