<?php

namespace App\Livewire\Admin;

use Livewire\Component;

use App\Models\PaymentGateway;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PaymentGatewayIndex extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $editingId = null;
    public $isModalOpen = false;

    // Form fields
    public $payment;
    public $category;
    public $biaya;
    public $biaya_type = 'rupiah';
    public $icon;
    public $currentIcon;
    public $is_active = true;

    // Delete confirmation
    public $deletingId = null;

    protected $rules = [
        'payment' => 'required|string|max:100',
        'category' => 'required|string',
        'biaya' => 'required|numeric',
        'biaya_type' => 'required|in:rupiah,persen',
        'icon' => 'nullable|image|max:2048',
        'is_active' => 'boolean',
    ];

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
            'biaya' => $this->biaya,
            'biaya_type' => $this->biaya_type,
            'is_active' => $this->is_active,
            'slug' => Str::slug($this->payment),
        ];

        if ($this->icon) {
            if ($this->editingId && $this->currentIcon) {
                Storage::disk('public')->delete($this->currentIcon);
            }
            $data['icon'] = $this->icon->store('payment-icons', 'public');
        }

        if ($this->editingId) {
            PaymentGateway::find($this->editingId)->update($data);
            session()->flash('message', 'Payment Gateway berhasil diperbarui.');
        } else {
            PaymentGateway::create($data);
            session()->flash('message', 'Payment Gateway berhasil ditambahkan.');
        }

        $this->dispatch('close-modal', name: 'payment-gateway-modal');
        $this->resetFields();
    }

    public function toggleStatus($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->is_active = !$gateway->is_active;
        $gateway->save();
        session()->flash('message', 'Status berhasil diperbarui.');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $gateway = PaymentGateway::findOrFail($id);
        
        // Check if used in Cart
        $inUse = \App\Models\Cart::where('payment_type', $gateway->slug)->exists();
        
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
            $inUse = \App\Models\Cart::where('payment_type', $gateway->slug)->exists();
            if ($inUse) {
                session()->flash('error', 'Gagal: Payment Gateway ini memiliki riwayat transaksi dan tidak dapat dihapus.');
                $this->dispatch('close-modal', name: 'delete-gateway-modal');
                return;
            }

            if ($gateway->icon) {
                Storage::disk('public')->delete($gateway->icon);
            }
            $gateway->delete();
            $this->dispatch('close-modal', name: 'delete-gateway-modal');
            $this->deletingId = null;
            session()->flash('message', 'Payment Gateway berhasil dihapus.');
        }
    }

    public function render()
    {
        $gateways = PaymentGateway::where('payment', 'like', '%' . $this->search . '%')
            ->orWhere('category', 'like', '%' . $this->search . '%')
            ->orderBy('category')
            ->orderBy('payment')
            ->paginate(10);

        return view('livewire.admin.payment-gateway-index', [
            'gateways' => $gateways
        ])->layout('admin.layout', ['title' => 'Payment Gateway']);
    }
}
