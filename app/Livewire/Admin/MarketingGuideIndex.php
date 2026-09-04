<?php

namespace App\Livewire\Admin;

use App\Models\MarketingGuideAccess;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MarketingGuideIndex extends Component
{
    use WithPagination;

    public const DURATION_OPTIONS = [1, 3, 7, 14, 30];

    public string $recipient_name = '';

    public int $duration_days = 7;

    public ?string $generatedUrl = null;

    public ?int $generatedAccessId = null;

    public ?int $extendingAccessId = null;

    public int $extend_days = 7;

    protected MarketingGuideAccessService $accessService;

    public function boot(MarketingGuideAccessService $accessService): void
    {
        $this->accessService = $accessService;
    }

    public function mount(): void
    {
        $this->ensureAdmin();
    }

    public function openCreateModal(): void
    {
        $this->ensureAdmin();
        $this->resetCreateForm();
        $this->dispatch('open-modal', name: 'marketing-guide-create-modal');
    }

    public function resetCreateForm(): void
    {
        $this->recipient_name = '';
        $this->duration_days = 7;
        $this->resetValidation();
    }

    public function generateLink(): void
    {
        $this->ensureAdmin();

        $this->validate([
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'duration_days' => ['required', 'integer', Rule::in(self::DURATION_OPTIONS)],
        ]);

        $creator = $this->adminUser();

        $created = $this->accessService->create(
            $creator,
            now()->addDays($this->duration_days),
            filled($this->recipient_name) ? trim($this->recipient_name) : null
        );

        $this->generatedAccessId = $created['access']->id;
        $this->generatedUrl = route('marketing-guide.show', ['token' => $created['token']], absolute: true);

        $this->dispatch('close-modal', name: 'marketing-guide-create-modal');
        $this->resetCreateForm();
        $this->resetPage();

        session()->flash('success', 'Link Marketing Guide berhasil dibuat. Salin URL sekarang; token tidak dapat dipulihkan lagi.');
    }

    public function regenerateLink(int $accessId): void
    {
        $this->ensureAdmin();

        $creator = $this->adminUser();

        $created = DB::transaction(function () use ($accessId, $creator) {
            $existing = MarketingGuideAccess::query()
                ->whereKey($accessId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->accessService->revoke($existing);

            return $this->accessService->create(
                $creator,
                now()->addDays(7),
                $existing->recipient_name
            );
        });

        if ($this->generatedAccessId === $accessId) {
            $this->generatedUrl = null;
            $this->generatedAccessId = null;
        }

        $this->generatedAccessId = $created['access']->id;
        $this->generatedUrl = route('marketing-guide.show', ['token' => $created['token']], absolute: true);
        $this->resetPage();

        session()->flash('success', 'Link baru berhasil digenerate ulang. Link lama telah di-revoke. Salin URL sekarang.');
    }

    public function openExtendModal(int $accessId): void
    {
        $this->ensureAdmin();

        MarketingGuideAccess::query()->findOrFail($accessId);
        $this->extendingAccessId = $accessId;
        $this->extend_days = 7;
        $this->resetValidation();
        $this->dispatch('open-modal', name: 'marketing-guide-extend-modal');
    }

    public function extendLink(): void
    {
        $this->ensureAdmin();

        $this->validate([
            'extendingAccessId' => ['required', 'integer', 'exists:marketing_guide_accesses,id'],
            'extend_days' => ['required', 'integer', Rule::in(self::DURATION_OPTIONS)],
        ]);

        $access = MarketingGuideAccess::query()->findOrFail($this->extendingAccessId);
        $this->accessService->extend($access, $this->extend_days);

        $this->dispatch('close-modal', name: 'marketing-guide-extend-modal');
        $this->extendingAccessId = null;

        session()->flash('success', 'Masa berlaku link berhasil diperpanjang.');
    }

    public function revokeLink(int $accessId): void
    {
        $this->ensureAdmin();

        $access = MarketingGuideAccess::query()->findOrFail($accessId);
        $this->accessService->revoke($access);

        if ($this->generatedAccessId === $accessId) {
            $this->generatedUrl = null;
            $this->generatedAccessId = null;
        }

        session()->flash('success', 'Link berhasil di-revoke dan tidak dapat digunakan.');
    }

    public function clearGeneratedUrl(): void
    {
        $this->ensureAdmin();

        $this->generatedUrl = null;
        $this->generatedAccessId = null;
    }

    public function render()
    {
        $this->ensureAdmin();

        $links = MarketingGuideAccess::query()
            ->latest('id')
            ->paginate(10);

        return view('livewire.admin.marketing-guide-index', [
            'links' => $links,
            'durationOptions' => self::DURATION_OPTIONS,
            'accessService' => $this->accessService,
        ])->layout('admin.layout', ['title' => 'Marketing Guide']);
    }

    private function ensureAdmin(): void
    {
        abort_unless($this->isAdmin(Auth::user()), 403);
    }

    private function adminUser(): User
    {
        $user = Auth::user();
        abort_unless($this->isAdmin($user), 403);

        return $user;
    }

    private function isAdmin(mixed $user): bool
    {
        return $user instanceof User
            && strtolower((string) $user->role) === 'admin';
    }
}
