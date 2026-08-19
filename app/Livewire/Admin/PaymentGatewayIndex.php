<?php

namespace App\Livewire\Admin;

use App\Models\Cart;
use App\Models\PaymentGateway;
use App\Services\SecureImageStorage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PaymentGatewayIndex extends Component
{
    use WithFileUploads, WithPagination;

    private const SUPPORTED_MIDTRANS_CODES = [
        'bca_va' => 'BCA Virtual Account',
        'bni_va' => 'BNI Virtual Account',
        'bri_va' => 'BRI Virtual Account',
        'cimb_va' => 'CIMB Virtual Account',
        'danamon_va' => 'Danamon Virtual Account',
        'bsi_va' => 'BSI Virtual Account',
        'permata_va' => 'Permata Virtual Account',
        'echannel' => 'Mandiri Bill Payment',
        'gopay' => 'GoPay',
        'shopeepay' => 'ShopeePay',
        'other_qris' => 'QRIS',
        'credit_card' => 'Credit Card',
        'alfamart' => 'Alfamart',
        'indomaret' => 'Indomaret',
    ];

    public $search = '';

    public $editingId = null;

    public $isModalOpen = false;

    // Form fields
    public $payment;

    public $category;

    public $biaya;

    public $biaya_type = 'rupiah';

    public $default_fee_fixed = 0;

    public $default_fee_percent = 0;

    public $midtrans_code;

    public $icon;

    public $currentIcon;

    public $is_active = true;

    // Delete confirmation
    public $deletingId = null;

    protected function rules(): array
    {
        return [
            'payment' => 'required|string|max:100',
            'category' => 'required|string',
            'default_fee_fixed' => ['required', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'default_fee_percent' => ['required', 'regex:/^\d{1,4}(\.\d{1,4})?$/'],
            'midtrans_code' => 'required|in:'.implode(',', array_keys(self::SUPPORTED_MIDTRANS_CODES)),
            'icon' => SecureImageStorage::rules(),
            'is_active' => 'boolean',
        ];
    }

    public function getMidtransCodeOptionsProperty(): array
    {
        return self::SUPPORTED_MIDTRANS_CODES;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetFields();
        $this->isModalOpen = true;
        $this->dispatch('open-modal', name: 'payment-gateway-modal');
    }

    public function resetFields()
    {
        $this->payment = '';
        $this->category = '';
        $this->biaya = '';
        $this->biaya_type = 'rupiah';
        $this->default_fee_fixed = 0;
        $this->default_fee_percent = 0;
        $this->midtrans_code = '';
        $this->icon = null;
        $this->currentIcon = null;
        $this->is_active = true;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function edit($id)
    {
        $this->resetFields();
        $gateway = PaymentGateway::findOrFail($id);
        $this->editingId = $id;
        $this->payment = $gateway->payment;
        $this->category = $gateway->category;
        $this->biaya = $gateway->biaya;
        $this->biaya_type = $gateway->biaya_type;
        $this->default_fee_fixed = $gateway->default_fee_fixed;
        $this->default_fee_percent = $gateway->default_fee_percent;
        $this->midtrans_code = $gateway->midtrans_code;
        $this->currentIcon = $gateway->icon;
        $this->is_active = $gateway->is_active;

        $this->isModalOpen = true;
        $this->dispatch('open-modal', name: 'payment-gateway-modal');
    }

    public function save()
    {
        $this->validate();

        $data = [
            'payment' => $this->payment,
            'category' => $this->category,
            'default_fee_fixed' => $this->default_fee_fixed,
            'default_fee_percent' => $this->default_fee_percent,
            'midtrans_code' => $this->midtrans_code,
            'is_active' => $this->is_active,
            'slug' => Str::slug($this->payment),
        ];

        $oldIcon = null;
        if ($this->icon) {
            $oldIcon = $this->editingId ? $this->currentIcon : null;
            $data['icon'] = app(SecureImageStorage::class)->store($this->icon, 'payment-icons');
        }

        if ($this->editingId) {
            PaymentGateway::find($this->editingId)->update($data);
            session()->flash('message', 'Payment Gateway berhasil diperbarui.');
        } else {
            $data['biaya'] = 0;
            $data['biaya_type'] = 'rupiah';
            PaymentGateway::create($data);
            session()->flash('message', 'Payment Gateway berhasil ditambahkan.');
        }

        app(SecureImageStorage::class)->delete('payment-icons', $oldIcon);
        app(SecureImageStorage::class)->delete('icon_payment', $oldIcon);

        $this->dispatch('close-modal', name: 'payment-gateway-modal');
        $this->resetFields();
    }

    public function toggleStatus($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->is_active = ! $gateway->is_active;
        $gateway->save();
        session()->flash('message', 'Status berhasil diperbarui.');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $gateway = PaymentGateway::findOrFail($id);

        // Check if used in Cart
        $inUse = Cart::where('payment_type', $gateway->slug)->exists();

        if ($inUse) {
            $this->dispatch('open-modal', name: 'cannot-delete-gateway-modal');
        } else {
            $this->dispatch('open-modal', name: 'delete-gateway-modal');
        }
    }

    public function delete()
    {
        if ($this->deletingId) {
            $gateway = PaymentGateway::findOrFail($this->deletingId);

            // double check
            $inUse = Cart::where('payment_type', $gateway->slug)->exists();
            if ($inUse) {
                session()->flash('error', 'Gagal: Payment Gateway ini memiliki riwayat transaksi dan tidak dapat dihapus.');
                $this->dispatch('close-modal', name: 'delete-gateway-modal');

                return;
            }

            app(SecureImageStorage::class)->delete('payment-icons', $gateway->icon);
            app(SecureImageStorage::class)->delete('icon_payment', $gateway->icon);
            $gateway->delete();
            $this->dispatch('close-modal', name: 'delete-gateway-modal');
            $this->deletingId = null;
            session()->flash('message', 'Payment Gateway berhasil dihapus.');
        }
    }

    public function render()
    {
        $gateways = PaymentGateway::where('payment', 'like', '%'.$this->search.'%')
            ->orWhere('category', 'like', '%'.$this->search.'%')
            ->orderBy('category')
            ->orderBy('payment')
            ->paginate(10);

        return view('livewire.admin.payment-gateway-index', [
            'gateways' => $gateways,
        ])->layout('admin.layout', ['title' => 'Payment Gateway']);
    }
}
