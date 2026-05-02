<?php

namespace App\Livewire\Admin;

use App\Models\Landing;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingIndex extends Component
{
    use WithFileUploads;

    public $activeTab = 'logo';

    // Settings fields
    public $description;
    public $keyword;
    public $logo;
    public $icon;

    // File fields
    public $new_logo;
    public $new_icon;

    public function mount()
    {
        $setting = Landing::first();
        if ($setting) {
            $this->description = $setting->description;
            $this->keyword = $setting->keyword;
            $this->logo = $setting->logo;
            $this->icon = $setting->icon;
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updateSEO()
    {
        $this->validate([
            'description' => 'required|string|max:500',
            'keyword' => 'required|string|max:255',
        ]);

        $setting = Landing::first() ?? new Landing();
        $setting->description = $this->description;
        $setting->keyword = $this->keyword;
        $setting->save();

        session()->flash('success', 'Pengaturan SEO berhasil diperbarui.');
    }

    public function updateLogo()
    {
        $this->validate([
            'new_logo' => 'required|image|max:2048',
        ]);

        $setting = Landing::first() ?? new Landing();

        if ($setting->logo && Storage::exists('public/logo/' . $setting->logo)) {
            Storage::delete('public/logo/' . $setting->logo);
        }

        $fileName = 'logo_' . time() . '.' . $this->new_logo->getClientOriginalExtension();
        $this->new_logo->storeAs('public/logo/', $fileName);
        
        $setting->logo = $fileName;
        $setting->save();

        $this->logo = $fileName;
        $this->new_logo = null;

        session()->flash('success', 'Logo berhasil diperbarui.');
    }

    public function updateIcon()
    {
        $this->validate([
            'new_icon' => 'required|image|max:1024',
        ]);

        $setting = Landing::first() ?? new Landing();

        if ($setting->icon && Storage::exists('public/icon/' . $setting->icon)) {
            Storage::delete('public/icon/' . $setting->icon);
        }

        $fileName = 'icon_' . time() . '.' . $this->new_icon->getClientOriginalExtension();
        $this->new_icon->storeAs('public/icon/', $fileName);
        
        $setting->icon = $fileName;
        $setting->save();

        $this->icon = $fileName;
        $this->new_icon = null;

        session()->flash('success', 'Icon berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.admin.setting-index')
            ->layout('admin.layout', ['title' => 'Pengaturan Sistem']);
    }
}
