<?php

namespace App\Livewire\Tutorials;

use App\Services\Tutorials\TutorialProgressService;
use Livewire\Component;

class TutorialManager extends Component
{
    private const TUTORIALS = ['dashboard.getting-started' => ['name' => 'Getting Started', 'type' => 'Checklist', 'route' => 'dashboard'], 'dashboard.overview' => ['name' => 'Dashboard Tour', 'type' => 'Tour', 'route' => 'dashboard'], 'event.setup' => ['name' => 'Event Setup Tour', 'type' => 'Tour', 'route' => 'dashboard.event.create'], 'event.tickets' => ['name' => 'Ticket Tour', 'type' => 'Tour', 'route' => 'dashboard.event', 'note' => 'Pilih event lalu buka tab tiket dan klik Tur Tiket.'], 'event.transactions' => ['name' => 'Transaction + Scanner Tour', 'type' => 'Tour', 'route' => 'dashboard.event', 'note' => 'Pilih event lalu buka tab transaksi dan klik Tur Transaksi.'], 'withdrawal.overview' => ['name' => 'Withdrawal Tour', 'type' => 'Tour', 'route' => 'dashboard.penarikan']];

    public function mount(): void
    {
        abort_unless(auth()->user()?->role === 'penyewa', 403);
    }

    public function resetTutorial(string $tutorialKey): void
    {
        $user = auth()->user();
        if (! $user || $user->role !== 'penyewa' || ! isset(self::TUTORIALS[$tutorialKey])) {
            return;
        } app(TutorialProgressService::class)->reset($user, $tutorialKey);
        if ($tutorialKey === 'dashboard.getting-started') {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function render()
    {
        $user = auth()->user();
        $progress = app(TutorialProgressService::class);
        $tutorials = collect(self::TUTORIALS)->map(function ($tutorial, $key) use ($user, $progress) {
            $tutorial['key'] = $key;
            $tutorial['status'] = $progress->isCompleted($user, $key) ? 'Selesai' : ($progress->isDismissed($user, $key) ? 'Dilewati' : 'Belum selesai');

            return $tutorial;
        });

        return view('livewire.tutorials.tutorial-manager', compact('tutorials'));
    }
}
