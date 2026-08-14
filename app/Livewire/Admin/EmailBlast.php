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
    public bool $showPreviewModal = false;
    public bool $showConfirmationModal = false;
    public string $previewHtml = '';
    public string $previewSubject = '';
    public int $pendingRecipientCount = 0;
    public array $pendingBlastPayload = [];
    public bool $isConfirmingSend = false;

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
        $this->resetBlastDialogs();
    }

    public function mount(): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);
    }

    public function previewBlast(): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $validated = $this->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $this->previewSubject = $validated['subject'];
        $this->previewHtml = view('emails.blast', [
            'content' => $validated['content'],
        ])->render();
        $this->showPreviewModal = true;
    }

    public function closePreview(): void
    {
        $this->showPreviewModal = false;
    }

    public function sendBlast(): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        $validated = $this->validate();
        $validated['users_selected'] = array_values(array_unique($validated['users_selected'] ?? []));

        $recipientQuery = $this->recipientQuery($validated);
        $totalRecipients = (clone $recipientQuery)->count();

        if ($totalRecipients === 0) {
            $this->showConfirmationModal = false;
            $this->pendingBlastPayload = [];
            $this->pendingRecipientCount = 0;
            session()->flash('error', 'Tidak ada pengguna yang sesuai dengan target yang dipilih.');

            return;
        }

        $this->pendingBlastPayload = $validated;
        $this->pendingRecipientCount = $totalRecipients;
        $this->showConfirmationModal = true;
    }

    public function cancelSendBlast(): void
    {
        $this->showConfirmationModal = false;
        $this->pendingBlastPayload = [];
        $this->pendingRecipientCount = 0;
        $this->isConfirmingSend = false;
    }

    public function confirmSendBlast(): void
    {
        abort_unless(strtolower((string) Auth::user()?->role) === 'admin', 403);

        if ($this->isConfirmingSend || ! $this->showConfirmationModal || $this->pendingBlastPayload === []) {
            return;
        }

        $this->isConfirmingSend = true;

        try {
            $payload = $this->pendingBlastPayload;
            $recipientQuery = $this->recipientQuery($payload);
            $totalRecipients = (clone $recipientQuery)->count();

            if ($totalRecipients === 0) {
                $this->cancelSendBlast();
                session()->flash('error', 'Tidak ada pengguna yang sesuai dengan target yang dipilih.');

                return;
            }

            $campaign = DB::transaction(function () use ($payload, $recipientQuery, $totalRecipients) {
            $campaign = EmailCampaign::create([
                'subject' => $payload['subject'],
                'content' => $payload['content'],
                'target_type' => $payload['targetType'],
                'event_uid' => $payload['targetType'] === 'event' ? $payload['event_uid'] : null,
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

            $this->reset([
                'subject',
                'content',
                'event_uid',
                'targetType',
                'users_selected',
                'showPreviewModal',
                'previewHtml',
                'previewSubject',
                'showConfirmationModal',
                'pendingRecipientCount',
                'pendingBlastPayload',
            ]);
            $this->targetType = 'all';

            session()->flash('success', 'Email blast sedang diproses untuk ' . $campaign->total_recipients . ' penerima.');
        } finally {
            $this->isConfirmingSend = false;
        }
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

    private function recipientQuery(?array $payload = null): Builder
    {
        $payload ??= [
            'targetType' => $this->targetType,
            'event_uid' => $this->event_uid,
            'users_selected' => array_values(array_unique($this->users_selected)),
        ];

        $query = User::query()
            ->where('role', User::USER_ROLE)
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if (($payload['targetType'] ?? 'all') === 'event') {
            $eventUid = $payload['event_uid'] ?? null;

            $query->whereHas('transactions', function ($transactionQuery) use ($eventUid) {
                $transactionQuery->where('event_uid', $eventUid)
                    ->where('status_transaksi', 'SUCCESS');
            });
        }

        if (($payload['targetType'] ?? 'all') === 'users') {
            $query->whereIn('uid', array_values(array_unique($payload['users_selected'] ?? [])));
        }

        return $query->orderBy('id');
    }

    private function resetBlastDialogs(): void
    {
        $this->showPreviewModal = false;
        $this->showConfirmationModal = false;
        $this->previewHtml = '';
        $this->previewSubject = '';
        $this->pendingRecipientCount = 0;
        $this->pendingBlastPayload = [];
        $this->isConfirmingSend = false;
    }
}
