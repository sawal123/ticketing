<?php

namespace App\Jobs;

use App\Mail\BlastEmail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class ProcessEmailBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $campaignId;
    public array $recipientIds;

    public function __construct(int $campaignId, array $recipientIds)
    {
        $this->campaignId = $campaignId;
        $this->recipientIds = $recipientIds;
    }

    public function handle(): void
    {
        $campaign = EmailCampaign::query()->find($this->campaignId);

        if (! $campaign) {
            return;
        }

        EmailCampaign::query()
            ->whereKey($campaign->id)
            ->where('status', EmailCampaign::STATUS_PENDING)
            ->update(['status' => EmailCampaign::STATUS_PROCESSING]);

        $recipients = EmailCampaignRecipient::query()
            ->where('email_campaign_id', $campaign->id)
            ->whereIn('id', $this->recipientIds)
            ->where('status', EmailCampaignRecipient::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new BlastEmail($campaign));

                $recipient->update([
                    'status' => EmailCampaignRecipient::STATUS_SENT,
                    'error_message' => null,
                    'sent_at' => now(),
                ]);

                $sentCount++;
            } catch (\Throwable $e) {
                $recipient->update([
                    'status' => EmailCampaignRecipient::STATUS_FAILED,
                    'error_message' => $this->safeErrorMessage($e),
                    'sent_at' => null,
                ]);

                $failedCount++;
            }
        }

        if ($sentCount > 0) {
            EmailCampaign::query()->whereKey($campaign->id)->increment('sent_count', $sentCount);
        }

        if ($failedCount > 0) {
            EmailCampaign::query()->whereKey($campaign->id)->increment('failed_count', $failedCount);
        }

        $this->syncCampaignStatus($campaign->id);
    }

    private function syncCampaignStatus(int $campaignId): void
    {
        $counts = EmailCampaignRecipient::query()
            ->where('email_campaign_id', $campaignId)
            ->selectRaw("
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
            ")
            ->first();

        $pendingCount = (int) ($counts?->pending_count ?? 0);
        $sentCount = (int) ($counts?->sent_count ?? 0);
        $failedCount = (int) ($counts?->failed_count ?? 0);

        $status = match (true) {
            $pendingCount > 0 => EmailCampaign::STATUS_PROCESSING,
            $sentCount === 0 && $failedCount > 0 => EmailCampaign::STATUS_FAILED,
            $failedCount > 0 => EmailCampaign::STATUS_COMPLETED_WITH_FAILURES,
            default => EmailCampaign::STATUS_COMPLETED,
        };

        EmailCampaign::query()->whereKey($campaignId)->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => $status,
        ]);
    }

    private function safeErrorMessage(\Throwable $exception): string
    {
        $message = trim(preg_replace('/\s+/', ' ', strip_tags($exception->getMessage())) ?? '');

        if ($message === '') {
            return 'Gagal mengirim email.';
        }

        return Str::limit($message, 180);
    }
}
