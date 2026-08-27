<?php

namespace App\Livewire\Admin;

use App\Models\Landing;
use App\Models\PlatformLegalProfile;
use App\Services\SecureImageStorage;
use Illuminate\Support\Facades\Auth;
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

    public $legalProfileId;

    public $company_name;

    public $legal_id;

    public $address;

    public $representative_name;

    public $representative_position;

    public $email;

    public $phone;

    public $website;

    // File fields
    public $new_logo;

    public $new_icon;

    public function mount()
    {
        $this->ensureAdmin();

        $setting = Landing::first();
        if ($setting) {
            $this->description = $setting->description;
            $this->keyword = $setting->keyword;
            $this->logo = $setting->logo;
            $this->icon = $setting->icon;
        }

        $this->fillLegalProfileFields($this->resolveLegalProfile());
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updateSEO()
    {
        $this->ensureAdmin();

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
        $this->ensureAdmin();

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
        $this->ensureAdmin();

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

    public function updateLegalProfile()
    {
        $this->ensureAdmin();
        $this->normalizeLegalProfileInput();

        $this->validate([
            'company_name' => 'nullable|string|max:255',
            'legal_id' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:1000',
            'representative_name' => 'nullable|string|max:255',
            'representative_position' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
        ]);

        $profile = PlatformLegalProfile::query()->updateOrCreate(
            ['profile_key' => PlatformLegalProfile::DEFAULT_KEY],
            [
                'company_name' => $this->company_name,
                'legal_id' => $this->legal_id,
                'address' => $this->address,
                'representative_name' => $this->representative_name,
                'representative_position' => $this->representative_position,
                'email' => $this->email,
                'phone' => $this->phone,
                'website' => $this->website,
            ]
        );

        $this->fillLegalProfileFields($profile);

        session()->flash('success', 'Identitas legal berhasil diperbarui.');
    }

    public function render()
    {
        $this->ensureAdmin();

        return view('livewire.admin.setting-index')
            ->layout('admin.layout', ['title' => 'Pengaturan Sistem']);
    }

    private function ensureAdmin(): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);
    }

    private function fillLegalProfileFields(?PlatformLegalProfile $profile): void
    {
        $this->company_name = $profile?->company_name;
        $this->legal_id = $profile?->legal_id;
        $this->address = $profile?->address;
        $this->representative_name = $profile?->representative_name;
        $this->representative_position = $profile?->representative_position;
        $this->email = $profile?->email;
        $this->phone = $profile?->phone;
        $this->website = $profile?->website;
    }

    private function resolveLegalProfile(): ?PlatformLegalProfile
    {
        return PlatformLegalProfile::query()
            ->where('profile_key', PlatformLegalProfile::DEFAULT_KEY)
            ->first();
    }

    private function normalizeLegalProfileInput(): void
    {
        foreach ([
            'company_name',
            'legal_id',
            'address',
            'representative_name',
            'representative_position',
            'email',
            'phone',
            'website',
        ] as $field) {
            $value = $this->{$field};

            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);
            $this->{$field} = $value === '' ? null : $value;
        }
    }
}
