<?php

namespace App\Livewire\Admin;

use App\Jobs\ProcessEmailBlast;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EmailBlast extends Component
{
    private const RECIPIENT_CHUNK_SIZE = 100;

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
            'event_uid' => 'exclude_unless:targetType,event|required',
            'users_selected' => 'exclude_unless:targetType,users|required|array|min:1',
            'users_selected.*' => 'exclude_unless:targetType,users|string|exists:users,uid',
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

    public function mount(): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);
    }

    public function sendBlast()
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $validated = $this->validate();
        $recipientQuery = $this->recipientQuery();
        $totalRecipients = (clone $recipientQuery)->count();

        if ($totalRecipients === 0) {
            session()->flash('error', 'Tidak ada pengguna yang sesuai dengan target yang dipilih.');

            return;
        }

        $campaign = DB::transaction(function () use ($validated, $recipientQuery, $totalRecipients) {
            $campaign = EmailCampaign::create([
                'subject' => $validated['subject'],
                'content' => $validated['content'],
                'target_type' => $validated['targetType'],
                'event_uid' => $validated['targetType'] === 'event' ? $validated['event_uid'] : null,
                'total_recipients' => $totalRecipients,
                'sent_count' => 0,
                'failed_count' => 0,
                'status' => EmailCampaign::STATUS_PENDING,
                'created_by' => (string) Auth::user()?->uid,
            ]);

            (clone $recipientQuery)
                ->select(['id', 'uid', 'email'])
                ->chunkById(self::RECIPIENT_CHUNK_SIZE, function ($users) use ($campaign) {
                    $now = now();
                    $rows = $users->map(fn (User $user) => [
                        'email_campaign_id' => $campaign->id,
                        'user_uid' => $user->uid,
                        'email' => $user->email,
                        'status' => EmailCampaignRecipient::STATUS_PENDING,
                        'error_message' => null,
                        'sent_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    EmailCampaignRecipient::insertOrIgnore($rows);
                }, 'id');

            return $campaign;
        });

        EmailCampaignRecipient::query()
            ->where('email_campaign_id', $campaign->id)
            ->orderBy('id')
            ->chunkById(self::RECIPIENT_CHUNK_SIZE, function ($recipients) use ($campaign) {
                ProcessEmailBlast::dispatch($campaign->id, $recipients->pluck('id')->all());
            });

        $this->reset(['subject', 'content', 'event_uid', 'targetType', 'users_selected']);
        $this->targetType = 'all';

        session()->flash('success', 'Email blast sedang diproses untuk ' . $campaign->total_recipients . ' penerima.');
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
            ->limit(50)
            ->get();

        $campaigns = EmailCampaign::query()
            ->with(['event:uid,event'])
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.admin.email-blast', compact('events', 'availableUsers', 'campaigns'))
            ->layout('admin.layout', ['title' => 'Email Blast']);
    }

    private function recipientQuery(): Builder
    {
        $query = User::query()
            ->where('role', User::USER_ROLE)
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($this->targetType === 'event') {
            $eventUid = $this->event_uid;

            $query->whereHas('transactions', function ($transactionQuery) use ($eventUid) {
                $transactionQuery->where('event_uid', $eventUid)
                    ->where('status_transaksi', 'SUCCESS');
            });
        }

        if ($this->targetType === 'users') {
            $query->whereIn('uid', array_values(array_unique($this->users_selected)));
        }

        return $query->orderBy('id');
    }
}
