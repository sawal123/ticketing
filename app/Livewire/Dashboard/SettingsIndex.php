<?php

namespace App\Livewire\Dashboard;

use App\Models\Bank;
use App\Models\BankIndonesia;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsIndex extends Component
{
    use WithFileUploads;

    public $activeTab = 'profile';

    // Profile Fields
    public $name, $email, $nomor, $birthday, $alamat, $kota, $gender, $gambar;
    public $new_gambar;

    // Password Fields
    public $current_password, $new_password, $new_password_confirmation;

    // Bank Fields
    public $banks = [];
    public $bank_id, $nama_rekening, $bank_name, $nomor_rekening;
    public $deletingBankId;
    public $isEditBank = false;
    public $available_banks = [];

    protected $listeners = ['refreshBanks' => 'loadBanks'];

    public function mount()
    {
        $user = Auth::user();
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
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'nomor' => 'required|numeric',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string',
            'kota' => 'nullable|string',
            'alamat' => 'nullable|string',
            'new_gambar' => 'nullable|image|max:2048',
        ]);

        $user = User::find(Auth::id());
        $user->name = $this->name;
        $user->email = $this->email;
        $user->nomor = $this->nomor;
        $user->birthday = $this->birthday;
        $user->gender = $this->gender;
        $user->kota = $this->kota;
        $user->alamat = $this->alamat;

        if ($this->new_gambar) {
            if ($user->gambar && Storage::exists('public/user/' . $user->gambar)) {
                Storage::delete('public/user/' . $user->gambar);
            }

            $fileName = $user->uid . '_' . time() . '.' . $this->new_gambar->getClientOriginalExtension();
            $this->new_gambar->storeAs('public/user/', $fileName);
            $user->gambar = $fileName;
            $this->gambar = $fileName;
            $this->new_gambar = null;
        }

        $user->save();
        session()->flash('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Password saat ini salah.');
            return;
        }

        $user->password = Hash::make($this->new_password);
        $user->save();

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
        $this->dispatch('open-modal', name: 'bank-modal');
    }

    public function saveBank()
    {
        $this->validate([
            'nama_rekening' => 'required|string|max:255',
            'bank_name' => 'required|string',
            'nomor_rekening' => 'required|numeric',
        ]);

        $ownerId = $this->getOwnerId();

        if ($this->isEditBank) {
            $bank = $this->bankQuery($ownerId)->findOrFail($this->bank_id);
        } else {
            $bank = $this->bankQuery($ownerId)->first();

            if ($bank && $bank->nama && $bank->bank && $bank->norek) {
                session()->flash('error', 'Maksimal hanya diperbolehkan 1 rekening bank.');
                $this->dispatch('close-modal', name: 'bank-modal');
                return;
            }

            $bank ??= new Bank();
            $bank->uid = $ownerId;
            $bank->uid_user = $ownerId;
        }

        $bank->nama = $this->nama_rekening;
        $bank->bank = $this->bank_name;
        $bank->norek = $this->nomor_rekening;
        $bank->save();

        $this->loadBanks();
        $this->dispatch('close-modal', name: 'bank-modal');
        session()->flash('success', $this->isEditBank ? 'Rekening berhasil diperbarui.' : 'Rekening berhasil ditambahkan.');
    }

    public function confirmDeleteBank($id)
    {
        $this->bankQuery($this->getOwnerId())->findOrFail($id);
        $this->deletingBankId = $id;
        $this->dispatch('open-modal', name: 'delete-bank-modal');
    }

    public function deleteBank()
    {
        if (! $this->deletingBankId) {
            return;
        }

        $this->bankQuery($this->getOwnerId())->findOrFail($this->deletingBankId)->delete();
        $this->deletingBankId = null;
        $this->loadBanks();
        $this->dispatch('close-modal', name: 'delete-bank-modal');
        session()->flash('success', 'Rekening berhasil dihapus.');
    }

    protected function getOwnerId()
    {
        $user = Auth::user();

        return $user->role === 'staff' ? $user->parent_uid : $user->uid;
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
