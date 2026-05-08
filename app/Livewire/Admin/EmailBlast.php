<?php

namespace App\Livewire\Admin;

use App\Jobs\ProcessEmailBlast;
use App\Models\Event;
use App\Models\User;
use Livewire\Component;

class EmailBlast extends Component
{
    public $targetType = 'all'; // 'all', 'event', 'users'
    public $event_uid = '';
    public $users_selected = []; // Array of user UIDs
    public $search_user = '';
    public $subject = '';
    public $content = '';

    protected function rules()
    {
        return [
            'targetType' => 'required|in:all,event,users',
            'event_uid' => 'required_if:targetType,event',
            'users_selected' => 'required_if:targetType,users|array|min:1',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }

    protected $messages = [
        'targetType.required' => 'Pilih target pengguna.',
        'event_uid.required_if' => 'Pilih event untuk target ini.',
        'users_selected.required_if' => 'Pilih setidaknya satu pengguna.',
        'users_selected.min' => 'Pilih setidaknya satu pengguna.',
        'subject.required' => 'Subjek email tidak boleh kosong.',
        'content.required' => 'Isi email tidak boleh kosong.',
    ];

    public function updatedTargetType()
    {
        $this->event_uid = '';
        $this->users_selected = [];
    }

    public function sendBlast()
    {
        $this->validate();

        $users = collect();

        if ($this->targetType === 'all') {
            // Get all regular users
            $users = User::where('role', User::USER_ROLE)->get();
        } elseif ($this->targetType === 'event') {
            // Get users who bought ticket for this event and transaction is SUCCESS
            $event_uid = $this->event_uid;
            $users = User::where('role', User::USER_ROLE)
                ->whereHas('transactions', function ($query) use ($event_uid) {
                    $query->where('event_uid', $event_uid)
                          ->where('status_transaksi', 'SUCCESS');
                })->get();
        } elseif ($this->targetType === 'users') {
            // Get specific selected users
            $users = User::whereIn('uid', $this->users_selected)->get();
        }

        if ($users->isEmpty()) {
            session()->flash('error', 'Tidak ada pengguna yang sesuai dengan target yang dipilih.');
            return;
        }

        // Dispatch job to send emails
        ProcessEmailBlast::dispatch($users, $this->subject, $this->content);

        // Reset form
        $this->reset(['subject', 'content', 'event_uid', 'targetType', 'users_selected']);
        $this->targetType = 'all';

        session()->flash('success', 'Email blast sedang dikirim ke ' . $users->count() . ' pengguna secara background.');
    }

    public function render()
    {
        $events = Event::orderBy('created_at', 'desc')->get();
        $availableUsers = User::where('role', User::USER_ROLE)
            ->when($this->search_user, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search_user . '%')
                      ->orWhere('email', 'like', '%' . $this->search_user . '%');
                });
            })
            ->limit(50) // Limit to avoid performance issues if too many users
            ->get();
        
        return view('livewire.admin.email-blast', compact('events', 'availableUsers'))->layout('admin.layout', ['title' => 'Email Blast']);
    }
}
