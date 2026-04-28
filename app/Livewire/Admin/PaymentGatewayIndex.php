<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class PaymentGatewayIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.payment-gateway-index')
            ->layout('admin.layout', ['title' => 'Payment Gateway']);
    }
}
