<?php

namespace App\Livewire\Dashboard;

use App\Models\Bank;
use App\Models\BankIndonesia;
use App\Models\User;
use App\Services\SecureImageStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsIndex extends Component
{
    use WithFileUploads;

    public $activeTab = 'profile';

    // Profile Fields
    public $name;

    public $email;

    public $nomor;

    public $birthday;

    public $alamat;

    public $kota;

    public $gender;

    public $gambar;

    public $new_gambar;

    // Password Fields
    public $current_password;

    public $new_password;

    public $new_password_confirmation;

    // Bank Fields
    public $banks = [];

    public $bank_id;

    public $nama_rekening;

    public $bank_name;

    public $nomor_rekening;

    public $bank_current_password;

    public $deletingBankId;

    public $deleteBankPassword;

    public $isEditBank = false;

    public $available_banks = [];

    protected $listeners = ['refreshBanks' => 'loadBanks'];

    public function mount()
    {
        $user = Auth::user();
        abort_unless($user?->role === 'penyewa', 403);

        $this->name = $user->name;
        $this->email = $user->email;
        $this->nomor = $user->nomor;
        $this->birthday = $user->birthday;
        $this->alamat = $user->alamat;
        $this->kota = $user->kota;
        $this->gender = $user->gender;
        $this->gambar = $user->gambar;

        $this->loadBanks();
        $this->available_banks = BankIndonesia::orderBy('name', 'asc')->get();
    }

    public function loadBanks()
    {
        $ownerId = $this->getOwnerId();

        $this->banks = $this->bankQuery($ownerId)
            ->where(function ($query) {
                $query->where('nama', '!=', '')
                    ->orWhere('bank', '!=', '')
                    ->orWhere('norek', '!=', '');
            })
            ->get();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'nomor' => 'nullable|string|max:30',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string|max:50',
            'kota' => 'nullable|string|max:100',
            'alamat' => 'nullable|string|max:500',
            'new_gambar' => SecureImageStorage::rules(),
        ]);

        $user = User::where('uid', Auth::user()->uid)->firstOrFail();
        $user->name = $this->name;
        $user->nomor = $this->nomor;
        $user->birthday = $this->birthday;
        $user->gender = $this->gender;
        $user->kota = $this->kota;
        $user->alamat = $this->alamat;

        $oldImage = null;
        if ($this->new_gambar) {
            $oldImage = $user->gambar;
            $user->gambar = app(SecureImageStorage::class)->storeBasename($this->new_gambar, 'user');
            $this->gambar = $user->gambar;
            $this->new_gambar = null;
        }

        $user->save();
        $this->email = $user->email;
        app(SecureImageStorage::class)->delete('user', $oldImage);

        session()->flash('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed|regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
        ], [
            'new_password.regex' => 'Password harus mengandung huruf dan angka.',
        ]);

        $user = User::where('uid', Auth::user()->uid)->firstOrFail();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Password saat ini salah.');

            return;
        }

        $user->forceFill([
            'password' => Hash::make($this->new_password),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()->delete();
        request()->session()->regenerate();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success', 'Password berhasil diubah.');
    }

    public function openBankModal($id = null)
    {
        $this->resetValidation();
        if ($id) {
            $bank = $this->bankQuery($this->getOwnerId())->findOrFail($id);
            $this->bank_id = $bank->id;
            $this->nama_rekening = $bank->nama;
            $this->bank_name = $bank->bank;
            $this->nomor_rekening = $bank->norek;
            $this->isEditBank = true;
        } else {
            $this->reset(['bank_id', 'nama_rekening', 'bank_name', 'nomor_rekening']);
            $this->isEditBank = false;
        }
        $this->bank_current_password = null;
        $this->dispatch('open-modal', name: 'bank-modal');
    }

    public function saveBank()
    {
        $this->validate([
            'bank_current_password' => 'required|string',
            'nama_rekening' => 'required|string|max:255',
            'bank_name' => 'required|string|max:100',
            'nomor_rekening' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
        ]);

        $ownerId = $this->getOwnerId();
        $user = User::where('uid', Auth::user()->uid)->firstOrFail();

        if (! Hash::check($this->bank_current_password, $user->password)) {
            $this->addError('bank_current_password', 'Password konfirmasi tidak sesuai.');

            return;
        }

        if ($this->isEditBank) {
            $bank = $this->bankQuery($ownerId)->findOrFail($this->bank_id);
        } else {
            $bank = $this->bankQuery($ownerId)->first();

            if ($bank && $bank->nama && $bank->bank && $bank->norek) {
                session()->flash('error', 'Maksimal hanya diperbolehkan 1 rekening bank.');
                $this->dispatch('close-modal', name: 'bank-modal');

                return;
            }

            $bank ??= new Bank;
            $bank->uid = $ownerId;
            $bank->uid_user = $ownerId;
        }

        $bank->nama = $this->nama_rekening;
        $bank->bank = $this->bank_name;
        $bank->norek = $this->nomor_rekening;
        $bank->save();

        $this->bank_current_password = null;
        $this->loadBanks();
        $this->dispatch('close-modal', name: 'bank-modal');
        session()->flash('success', $this->isEditBank ? 'Rekening berhasil diperbarui.' : 'Rekening berhasil ditambahkan.');
    }

    public function confirmDeleteBank($id)
    {
        $this->bankQuery($this->getOwnerId())->findOrFail($id);
        $this->deletingBankId = $id;
        $this->deleteBankPassword = null;
        $this->dispatch('open-modal', name: 'delete-bank-modal');
    }

    public function deleteBank()
    {
        if (! $this->deletingBankId) {
            return;
        }

        $this->validate([
            'deleteBankPassword' => 'required|string',
        ]);

        $user = Auth::user();

        if ($user->role === 'staff') {
            abort(403);
        }

        if (! Hash::check($this->deleteBankPassword, $user->password)) {
            $this->addError('deleteBankPassword', 'Password saat ini tidak sesuai.');
            $this->deleteBankPassword = null;

            return;
        }

        $bank = $this->bankQuery($this->getOwnerId())->find($this->deletingBankId);

        if (! $bank) {
            $this->addError('deleteBankPassword', 'Rekening tidak ditemukan atau bukan milik Anda.');
            $this->deleteBankPassword = null;

            return;
        }

        $bank->delete();

        $this->deletingBankId = null;
        $this->deleteBankPassword = null;
        $this->loadBanks();
        $this->dispatch('close-modal', name: 'delete-bank-modal');
        session()->flash('success', 'Rekening berhasil dihapus.');
    }

    protected function getOwnerId()
    {
        return Auth::user()->uid;
    }

    protected function bankQuery($ownerId)
    {
        return Bank::where(function ($query) use ($ownerId) {
            $query->where('uid_user', $ownerId)
                ->orWhere('uid', $ownerId);
        });
    }

    public function render()
    {
        return view('livewire.dashboard.settings-index')->layout('layouts.unified');
    }
}
