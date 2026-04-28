<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class SettingIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.setting-index')
            ->layout('admin.layout', ['title' => 'Pengaturan Sistem']);
    }
}
