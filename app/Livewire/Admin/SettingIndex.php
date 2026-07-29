<?php

namespace App\Livewire\Admin;

use App\Models\Landing;
use App\Services\SecureImageStorage;
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

        $setting = Landing::first() ?? new Landing;
        $setting->description = $this->description;
        $setting->keyword = $this->keyword;
        $setting->save();

        session()->flash('success', 'Pengaturan SEO berhasil diperbarui.');
    }

    public function updateLogo()
    {
        $this->validate([
            'new_logo' => SecureImageStorage::rules(true),
        ]);

        $setting = Landing::first() ?? new Landing;

        $oldLogo = $setting->logo;
        $setting->logo = app(SecureImageStorage::class)
            ->storeBasename($this->new_logo, 'logo');
        $setting->save();
        app(SecureImageStorage::class)->delete('logo', $oldLogo);

        $this->logo = $setting->logo;
        $this->new_logo = null;

        session()->flash('success', 'Logo berhasil diperbarui.');
    }

    public function updateIcon()
    {
        $this->validate([
            'new_icon' => SecureImageStorage::rules(true),
        ]);

        $setting = Landing::first() ?? new Landing;

        $oldIcon = $setting->icon;
        $setting->icon = app(SecureImageStorage::class)
            ->storeBasename($this->new_icon, 'icon');
        $setting->save();
        app(SecureImageStorage::class)->delete('icon', $oldIcon);

        $this->icon = $setting->icon;
        $this->new_icon = null;

        session()->flash('success', 'Icon berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.admin.setting-index')
            ->layout('admin.layout', ['title' => 'Pengaturan Sistem']);
    }
}
