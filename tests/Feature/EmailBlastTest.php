<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Jobs\ProcessEmailBlast;
use App\Livewire\Admin\EmailBlast;
use App\Mail\BlastEmail;
use App\Models\Cart;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\User;
use App\Support\EmailBlastSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EmailBlastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '']]);
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
    }

    public function test_campaign_is_created_for_all_target_and_jobs_are_chunked_by_identifier(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-email-blast@example.test']);

        foreach (range(1, 205) as $index) {
            $this->user([
                'email' => "member-{$index}@example.test",
                'role' => User::USER_ROLE,
            ]);
        }

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('targetType', 'all')
            ->set('subject', 'Blast Semua')
            ->set('content', '<p>Halo semua</p>')
            ->call('sendBlast')
            ->assertSet('showConfirmationModal', true)
            ->assertSet('pendingRecipientCount', 205)
            ->assertSee('Email ini akan dikirim ke 205 pengguna.')
            ->assertSee('Lanjutkan?')
            ->call('confirmSendBlast')
            ->assertHasNoErrors();

        $campaign = EmailCampaign::query()->firstOrFail();

        $this->assertSame('Blast Semua', $campaign->subject);
        $this->assertSame('all', $campaign->target_type);
        $this->assertSame(205, $campaign->total_recipients);
        $this->assertSame(205, EmailCampaignRecipient::query()->where('email_campaign_id', $campaign->id)->count());
        $this->assertSame(EmailCampaign::STATUS_PENDING, $campaign->status);

        Queue::assertPushed(ProcessEmailBlast::class, 3);
        Queue::assertPushed(ProcessEmailBlast::class, function (ProcessEmailBlast $job) use ($campaign) {
            return $job->campaignId === $campaign->id
                && is_array($job->recipientIds)
                && count($job->recipientIds) <= 100
                && collect($job->recipientIds)->every(fn ($id) => is_int($id));
        });
    }

    public function test_preview_does_not_create_campaign_or_dispatch_jobs_and_shows_rendered_email(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-preview@example.test']);

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('subject', 'Preview Subject')
            ->set('content', '<p>Halo Preview</p>')
            ->call('previewBlast')
            ->assertSet('showPreviewModal', true)
            ->assertSee('Preview Subject')
            ->assertSee('Halo Preview');

        $this->assertDatabaseCount('email_campaigns', 0);
        $this->assertDatabaseCount('email_campaign_recipients', 0);
        Queue::assertNothingPushed();
    }

    public function test_target_selector_uses_explicit_click_binding_without_wire_model(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-binding@example.test']);

        $component = Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->assertSeeHtml('wire:click="setTargetType(\'all\')"')
            ->assertSeeHtml('wire:click="setTargetType(\'buyers\')"')
            ->assertSeeHtml('wire:click="setTargetType(\'event\')"')
            ->assertSeeHtml('wire:click="setTargetType(\'users\')"')
            ->assertDontSeeHtml('wire:model.live="targetType"')
            ->assertDontSeeHtml('wire:model="targetType"')
            ->assertDontSeeHtml('$set')
            ->assertDontSeeHtml('$commit');

        $component
            ->set('targetType', 'users')
            ->assertSeeHtml('wire:input.debounce.300ms="updateSearchUser($event.target.value)"')
            ->assertDontSeeHtml('wire:model.live.debounce.300ms="search_user"');
    }

    public function test_target_type_change_to_event_and_users_updates_conditional_ui(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-target-ui@example.test']);
        $owner = $this->user(['role' => 'penyewa', 'email' => 'owner-target-ui@example.test']);
        $event = $this->event($owner);
        $selectedUser = $this->user(['email' => 'selected-target-ui@example.test', 'role' => User::USER_ROLE]);

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->call('setTargetType', 'event')
            ->assertSet('targetType', 'event')
            ->assertSee('Pilih Event')
            ->assertSee($event->event)
            ->call('setTargetType', 'users')
            ->assertSet('targetType', 'users')
            ->assertSeeHtml('wire:input.debounce.300ms="updateSearchUser($event.target.value)"')
            ->assertSee($selectedUser->email);
    }

    public function test_set_target_type_can_return_to_all_and_resets_previous_selection(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-reset-target@example.test']);
        $owner = $this->user(['role' => 'penyewa', 'email' => 'owner-reset-target@example.test']);
        $event = $this->event($owner);
        $selectedUser = $this->user(['email' => 'selected-reset@example.test', 'role' => User::USER_ROLE]);

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('event_uid', $event->uid)
            ->set('users_selected', [$selectedUser->uid])
            ->set('search_user', 'selected-reset')
            ->call('setTargetType', 'users')
            ->assertSet('targetType', 'users')
            ->assertSet('event_uid', '')
            ->assertSet('users_selected', [])
            ->assertSet('search_user', '')
            ->call('setTargetType', 'all')
            ->assertSet('targetType', 'all')
            ->assertSet('event_uid', '')
            ->assertSet('users_selected', []);
    }

    public function test_invalid_target_type_is_rejected(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-invalid-target@example.test']);

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->call('setTargetType', 'all')
            ->assertSet('targetType', 'all')
            ->call('setTargetType', 'invalid')
            ->assertSet('targetType', 'all');
    }

    public function test_search_user_updates_without_wire_commit_binding(): void
    {
        $admin = $this->user(['role' => 'admin', 'email' => 'admin-search-user@example.test']);
        $matchedUser = $this->user(['email' => 'matched-user@example.test', 'role' => User::USER_ROLE]);
        $this->user(['email' => 'other-user@example.test', 'role' => User::USER_ROLE]);

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->call('setTargetType', 'users')
            ->call('updateSearchUser', 'matched-user')
            ->assertSet('search_user', 'matched-user')
            ->assertSee($matchedUser->email)
            ->assertDontSee('other-user@example.test');
    }

    public function test_sanitizer_preserves_safe_html_and_removes_dangerous_elements(): void
    {
        $dirtyHtml = '<p onclick="alert(1)">Halo <strong>aman</strong></p><script>alert(1)</script><ul><li>Item</li></ul><a href="javascript:alert(1)" onload="x()">Klik</a><img src=x onerror=alert(1)>';
        $sanitized = EmailBlastSanitizer::sanitize($dirtyHtml);

        $this->assertStringContainsString('<p>Halo <strong>aman</strong></p>', $sanitized);
        $this->assertStringContainsString('<ul><li>Item</li></ul>', $sanitized);
        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('onclick=', $sanitized);
        $this->assertStringNotContainsString('onerror=', $sanitized);
        $this->assertStringNotContainsString('onload=', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringNotContainsString('<img', $sanitized);
    }

    public function test_sanitizer_removes_nested_dangerous_tag_inside_non_allowed_wrapper(): void
    {
        $sanitized = EmailBlastSanitizer::sanitize('<div><script>alert(1)</script><p>Aman</p></div>');

        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('alert(1)', $sanitized);
        $this->assertStringContainsString('<p>Aman</p>', $sanitized);
    }

    public function test_sanitizer_removes_nested_event_handler_inside_non_allowed_wrapper(): void
    {
        $sanitized = EmailBlastSanitizer::sanitize('<section><p onclick="alert(1)">Halo</p></section>');

        $this->assertStringContainsString('<p>Halo</p>', $sanitized);
        $this->assertStringNotContainsString('onclick=', $sanitized);
        $this->assertStringNotContainsString('alert(1)', $sanitized);
    }

    public function test_sanitizer_removes_dangerous_tags_inside_multiple_wrapper_layers(): void
    {
        $sanitized = EmailBlastSanitizer::sanitize('<div><section><article><iframe src="https://evil.test"></iframe><p>Layer Aman</p></article></section></div>');

        $this->assertStringNotContainsString('<iframe', $sanitized);
        $this->assertStringNotContainsString('evil.test', $sanitized);
        $this->assertStringContainsString('<p>Layer Aman</p>', $sanitized);
    }

    public function test_preview_uses_sanitized_content(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-preview-sanitized@example.test']);

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('subject', 'Preview Aman')
            ->set('content', '<p onload="alert(1)">Halo <strong>Preview</strong></p><script>alert(2)</script>')
            ->call('previewBlast')
            ->assertSet('showPreviewModal', true)
            ->assertSee('Halo')
            ->assertSee('Preview')
            ->assertDontSee('alert(2)')
            ->assertDontSee('onload=');

        $this->assertDatabaseCount('email_campaigns', 0);
        Queue::assertNothingPushed();
    }

    public function test_event_target_only_uses_success_transactions_without_duplicate_recipients(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-event@example.test']);
        $owner = $this->user(['role' => 'penyewa', 'email' => 'owner-event@example.test']);
        $event = $this->event($owner);
        $otherEvent = $this->event($owner, 'other');

        $successA = $this->user(['email' => 'success-a@example.test', 'role' => User::USER_ROLE]);
        $successB = $this->user(['email' => 'success-b@example.test', 'role' => User::USER_ROLE]);
        $failedUser = $this->user(['email' => 'failed@example.test', 'role' => User::USER_ROLE]);
        $otherEventUser = $this->user(['email' => 'other-event@example.test', 'role' => User::USER_ROLE]);

        $this->successfulTransaction($successA, $event, 'A1');
        $this->successfulTransaction($successA, $event, 'A2');
        $this->successfulTransaction($successB, $event, 'B1');
        $this->transaction($failedUser, $event, 'FAILED', 'F1');
        $this->successfulTransaction($otherEventUser, $otherEvent, 'O1');

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('targetType', 'event')
            ->set('event_uid', $event->uid)
            ->set('subject', 'Blast Event')
            ->set('content', '<p>Halo pembeli event</p>')
            ->call('sendBlast')
            ->assertSet('showConfirmationModal', true)
            ->assertSet('pendingRecipientCount', 2)
            ->assertSee('Email ini akan dikirim ke 2 pengguna.')
            ->assertSee('Lanjutkan?')
            ->call('confirmSendBlast')
            ->assertHasNoErrors();

        $campaign = EmailCampaign::query()->firstOrFail();
        $recipientUids = EmailCampaignRecipient::query()
            ->where('email_campaign_id', $campaign->id)
            ->orderBy('user_uid')
            ->pluck('user_uid')
            ->all();
        $expectedUids = collect([$successA->uid, $successB->uid])->sort()->values()->all();

        $this->assertSame('event', $campaign->target_type);
        $this->assertSame($event->uid, $campaign->event_uid);
        $this->assertSame(2, $campaign->total_recipients);
        $this->assertSame($expectedUids, $recipientUids);
    }

    public function test_buyers_target_only_uses_users_with_success_transactions_without_duplicates_or_missing_email(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-buyers@example.test']);
        $owner = $this->user(['role' => 'penyewa', 'email' => 'owner-buyers@example.test']);
        $eventA = $this->event($owner, 'buyers-a');
        $eventB = $this->event($owner, 'buyers-b');

        $buyerA = $this->user(['email' => 'buyer-a@example.test', 'role' => User::USER_ROLE]);
        $buyerB = $this->user(['email' => 'buyer-b@example.test', 'role' => User::USER_ROLE]);
        $failedOnly = $this->user(['email' => 'failed-only@example.test', 'role' => User::USER_ROLE]);
        $pendingOnly = $this->user(['email' => 'pending-only@example.test', 'role' => User::USER_ROLE]);
        $noEmailBuyer = $this->user(['email' => '', 'role' => User::USER_ROLE]);

        $this->successfulTransaction($buyerA, $eventA, 'BUY1');
        $this->successfulTransaction($buyerA, $eventB, 'BUY2');
        $this->successfulTransaction($buyerB, $eventA, 'BUY3');
        $this->transaction($failedOnly, $eventA, 'FAILED', 'BUY4');
        $this->transaction($pendingOnly, $eventA, 'PENDING', 'BUY5');
        $this->successfulTransaction($noEmailBuyer, $eventA, 'BUY6');

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->call('setTargetType', 'buyers')
            ->set('subject', 'Blast Pembeli')
            ->set('content', '<p>Halo pembeli</p>')
            ->call('sendBlast')
            ->assertSet('showConfirmationModal', true)
            ->assertSet('pendingRecipientCount', 2)
            ->assertSee('Email ini akan dikirim ke 2 pengguna.')
            ->call('confirmSendBlast')
            ->assertHasNoErrors();

        $campaign = EmailCampaign::query()->firstOrFail();
        $recipientUids = EmailCampaignRecipient::query()
            ->where('email_campaign_id', $campaign->id)
            ->pluck('user_uid')
            ->sort()
            ->values()
            ->all();
        $expectedUids = collect([$buyerA->uid, $buyerB->uid])->sort()->values()->all();

        $this->assertSame('buyers', $campaign->target_type);
        $this->assertSame(2, $campaign->total_recipients);
        $this->assertSame($expectedUids, $recipientUids);
        $this->assertNotContains($failedOnly->uid, $recipientUids);
        $this->assertNotContains($pendingOnly->uid, $recipientUids);
        $this->assertNotContains($noEmailBuyer->uid, $recipientUids);
    }

    public function test_users_target_uses_selected_users_without_duplicates(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-selected@example.test']);
        $userA = $this->user(['email' => 'selected-a@example.test', 'role' => User::USER_ROLE]);
        $userB = $this->user(['email' => 'selected-b@example.test', 'role' => User::USER_ROLE]);
        $userC = $this->user(['email' => 'selected-c@example.test', 'role' => User::USER_ROLE]);

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('targetType', 'users')
            ->set('users_selected', [$userA->uid, $userA->uid, $userC->uid])
            ->set('subject', 'Blast Pilihan')
            ->set('content', '<p>Halo user pilihan</p>')
            ->call('sendBlast')
            ->assertSet('showConfirmationModal', true)
            ->assertSet('pendingRecipientCount', 2)
            ->assertSee('Email ini akan dikirim ke 2 pengguna.')
            ->assertSee('Lanjutkan?')
            ->call('confirmSendBlast')
            ->assertHasNoErrors();

        $campaign = EmailCampaign::query()->firstOrFail();
        $recipientUids = EmailCampaignRecipient::query()
            ->where('email_campaign_id', $campaign->id)
            ->pluck('user_uid')
            ->sort()
            ->values()
            ->all();
        $expectedUids = collect([$userA->uid, $userC->uid])->sort()->values()->all();

        $this->assertSame('users', $campaign->target_type);
        $this->assertSame(2, $campaign->total_recipients);
        $this->assertSame($expectedUids, $recipientUids);
        $this->assertNotContains($userB->uid, $recipientUids);
    }

    public function test_campaign_stores_sanitized_content_and_email_uses_sanitized_html(): void
    {
        Mail::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-sanitize-campaign@example.test']);
        $buyer = $this->user(['email' => 'sanitize-buyer@example.test', 'role' => User::USER_ROLE]);
        $owner = $this->user(['role' => 'penyewa', 'email' => 'owner-sanitize@example.test']);
        $event = $this->event($owner, 'sanitize');

        $this->successfulTransaction($buyer, $event, 'SAN1');

        $dirtyHtml = '<h2 onclick="alert(1)">Promo</h2><p>Halo <strong>Pembeli</strong></p><a href="javascript:alert(2)" onerror="alert(3)">Link</a><script>alert(4)</script>';

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->call('setTargetType', 'buyers')
            ->set('subject', 'Sanitized Campaign')
            ->set('content', $dirtyHtml)
            ->call('sendBlast')
            ->call('confirmSendBlast')
            ->assertHasNoErrors();

        $campaign = EmailCampaign::query()->firstOrFail();

        $this->assertStringContainsString('<h2>Promo</h2>', $campaign->content);
        $this->assertStringContainsString('<p>Halo <strong>Pembeli</strong></p>', $campaign->content);
        $this->assertStringNotContainsString('<script', $campaign->content);
        $this->assertStringNotContainsString('onclick=', $campaign->content);
        $this->assertStringNotContainsString('onerror=', $campaign->content);
        $this->assertStringNotContainsString('javascript:', $campaign->content);

        $mailable = new BlastEmail($campaign);
        $rendered = $mailable->render();

        $this->assertStringContainsString('<h2>Promo</h2>', $rendered);
        $this->assertStringContainsString('<strong>Pembeli</strong>', $rendered);
        $this->assertStringNotContainsString('<script', $rendered);
        $this->assertStringNotContainsString('onclick=', $rendered);
        $this->assertStringNotContainsString('javascript:', $rendered);
    }

    public function test_process_job_marks_campaign_processing_then_completed_and_updates_counters(): void
    {
        Mail::fake();

        $campaign = EmailCampaign::create([
            'subject' => 'Progress Blast',
            'content' => '<p>Progress</p>',
            'target_type' => 'users',
            'event_uid' => null,
            'total_recipients' => 3,
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => EmailCampaign::STATUS_PENDING,
            'created_by' => 'admin-uid',
        ]);

        $first = $this->recipient($campaign, 'first@example.test', 'uid-1');
        $second = $this->recipient($campaign, 'second@example.test', 'uid-2');
        $third = $this->recipient($campaign, 'third@example.test', 'uid-3');

        (new ProcessEmailBlast($campaign->id, [$first->id, $second->id]))->handle();

        $campaign->refresh();

        $this->assertSame(EmailCampaign::STATUS_PROCESSING, $campaign->status);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertSame(EmailCampaignRecipient::STATUS_SENT, $first->fresh()->status);
        $this->assertSame(EmailCampaignRecipient::STATUS_SENT, $second->fresh()->status);
        $this->assertSame(EmailCampaignRecipient::STATUS_PENDING, $third->fresh()->status);

        (new ProcessEmailBlast($campaign->id, [$third->id]))->handle();

        $campaign->refresh();

        $this->assertSame(EmailCampaign::STATUS_COMPLETED, $campaign->status);
        $this->assertSame(3, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertSame(EmailCampaignRecipient::STATUS_SENT, $third->fresh()->status);
        Mail::assertSent(BlastEmail::class, 3);
    }

    public function test_failure_for_one_recipient_does_not_stop_others_and_campaign_can_complete_with_failures(): void
    {
        $campaign = EmailCampaign::create([
            'subject' => 'Mixed Result Blast',
            'content' => '<p>Mixed</p>',
            'target_type' => 'users',
            'event_uid' => null,
            'total_recipients' => 2,
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => EmailCampaign::STATUS_PENDING,
            'created_by' => 'admin-uid',
        ]);

        $successRecipient = $this->recipient($campaign, 'ok@example.test', 'uid-ok');
        $failedRecipient = $this->recipient($campaign, 'fail@example.test', 'uid-fail');

        Mail::shouldReceive('to')->once()->with('ok@example.test')->andReturn(new class
        {
            public function send($mailable): void
            {
            }
        });
        Mail::shouldReceive('to')->once()->with('fail@example.test')->andReturn(new class
        {
            public function send($mailable): void
            {
                throw new \RuntimeException('SMTP timeout');
            }
        });

        (new ProcessEmailBlast($campaign->id, [$successRecipient->id, $failedRecipient->id]))->handle();

        $campaign->refresh();

        $this->assertSame(EmailCampaign::STATUS_COMPLETED_WITH_FAILURES, $campaign->status);
        $this->assertSame(1, $campaign->sent_count);
        $this->assertSame(1, $campaign->failed_count);
        $this->assertSame(EmailCampaignRecipient::STATUS_SENT, $successRecipient->fresh()->status);
        $this->assertSame(EmailCampaignRecipient::STATUS_FAILED, $failedRecipient->fresh()->status);
        $this->assertSame('SMTP timeout', $failedRecipient->fresh()->error_message);
    }

    public function test_campaign_is_marked_failed_when_all_recipients_fail(): void
    {
        $campaign = EmailCampaign::create([
            'subject' => 'All Failed Blast',
            'content' => '<p>Fail</p>',
            'target_type' => 'users',
            'event_uid' => null,
            'total_recipients' => 1,
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => EmailCampaign::STATUS_PENDING,
            'created_by' => 'admin-uid',
        ]);

        $recipient = $this->recipient($campaign, 'broken@example.test', 'uid-broken');

        Mail::shouldReceive('to')->once()->with('broken@example.test')->andReturn(new class
        {
            public function send($mailable): void
            {
                throw new \RuntimeException('Mailbox unavailable');
            }
        });

        (new ProcessEmailBlast($campaign->id, [$recipient->id]))->handle();

        $campaign->refresh();

        $this->assertSame(EmailCampaign::STATUS_FAILED, $campaign->status);
        $this->assertSame(0, $campaign->sent_count);
        $this->assertSame(1, $campaign->failed_count);
        $this->assertSame(EmailCampaignRecipient::STATUS_FAILED, $recipient->fresh()->status);
    }

    public function test_reconciliation_uses_latest_recipient_state_to_finish_processing_campaign(): void
    {
        $campaign = EmailCampaign::create([
            'subject' => 'Reconcile Final',
            'content' => '<p>Final</p>',
            'target_type' => 'users',
            'event_uid' => null,
            'total_recipients' => 3,
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => EmailCampaign::STATUS_PROCESSING,
            'created_by' => 'admin-uid',
        ]);

        $sentA = $this->recipient($campaign, 'sent-a@example.test', 'uid-sent-a');
        $sentB = $this->recipient($campaign, 'sent-b@example.test', 'uid-sent-b');
        $failed = $this->recipient($campaign, 'failed@example.test', 'uid-failed');

        $sentA->update(['status' => EmailCampaignRecipient::STATUS_SENT, 'sent_at' => now()]);
        $sentB->update(['status' => EmailCampaignRecipient::STATUS_SENT, 'sent_at' => now()]);
        $failed->update(['status' => EmailCampaignRecipient::STATUS_FAILED, 'error_message' => 'SMTP timeout']);

        (new ProcessEmailBlast($campaign->id, []))->handle();

        $campaign->refresh();

        $this->assertSame(EmailCampaign::STATUS_COMPLETED_WITH_FAILURES, $campaign->status);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(1, $campaign->failed_count);
    }

    public function test_completed_campaign_does_not_return_to_processing_when_no_recipient_is_pending(): void
    {
        $campaign = EmailCampaign::create([
            'subject' => 'Stable Final',
            'content' => '<p>Stable</p>',
            'target_type' => 'users',
            'event_uid' => null,
            'total_recipients' => 2,
            'sent_count' => 0,
            'failed_count' => 99,
            'status' => EmailCampaign::STATUS_COMPLETED,
            'created_by' => 'admin-uid',
        ]);

        $sent = $this->recipient($campaign, 'sent@example.test', 'uid-sent');
        $done = $this->recipient($campaign, 'done@example.test', 'uid-done');

        $sent->update(['status' => EmailCampaignRecipient::STATUS_SENT, 'sent_at' => now()]);
        $done->update(['status' => EmailCampaignRecipient::STATUS_SENT, 'sent_at' => now()]);

        (new ProcessEmailBlast($campaign->id, [$sent->id, $done->id]))->handle();

        $campaign->refresh();

        $this->assertSame(EmailCampaign::STATUS_COMPLETED, $campaign->status);
        $this->assertNotSame(EmailCampaign::STATUS_PROCESSING, $campaign->status);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
    }

    public function test_non_admin_cannot_run_email_blast(): void
    {
        $user = $this->user(['role' => User::USER_ROLE, 'email' => 'non-admin@example.test']);

        Livewire::actingAs($user)
            ->test(EmailBlast::class)
            ->assertForbidden();
    }

    public function test_cancel_confirmation_does_not_create_campaign_or_dispatch_job(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-cancel@example.test']);
        $this->user(['email' => 'member-cancel@example.test', 'role' => User::USER_ROLE]);

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('targetType', 'all')
            ->set('subject', 'Batal Blast')
            ->set('content', '<p>Belum jadi</p>')
            ->call('sendBlast')
            ->assertSet('showConfirmationModal', true)
            ->call('cancelSendBlast')
            ->assertSet('showConfirmationModal', false);

        $this->assertDatabaseCount('email_campaigns', 0);
        $this->assertDatabaseCount('email_campaign_recipients', 0);
        Queue::assertNothingPushed();
    }

    public function test_confirm_click_repeatedly_only_creates_one_campaign(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-double-confirm@example.test']);

        foreach (range(1, 3) as $index) {
            $this->user([
                'email' => "double-confirm-{$index}@example.test",
                'role' => User::USER_ROLE,
            ]);
        }

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('targetType', 'all')
            ->set('subject', 'Single Campaign')
            ->set('content', '<p>Sekali saja</p>')
            ->call('sendBlast')
            ->call('confirmSendBlast')
            ->call('confirmSendBlast');

        $this->assertDatabaseCount('email_campaigns', 1);
        $this->assertDatabaseCount('email_campaign_recipients', 3);
        Queue::assertPushed(ProcessEmailBlast::class, 1);
    }

    public function test_zero_recipient_cannot_continue_to_send_process(): void
    {
        Queue::fake();

        $admin = $this->user(['role' => 'admin', 'email' => 'admin-zero@example.test']);
        $owner = $this->user(['role' => 'penyewa', 'email' => 'owner-zero@example.test']);
        $event = $this->event($owner);
        $this->transaction($this->user(['email' => 'failed-only@example.test', 'role' => User::USER_ROLE]), $event, 'FAILED', 'Z1');

        Livewire::actingAs($admin)
            ->test(EmailBlast::class)
            ->set('targetType', 'event')
            ->set('event_uid', $event->uid)
            ->set('subject', 'Zero Recipient')
            ->set('content', '<p>Tidak ada penerima</p>')
            ->call('sendBlast')
            ->assertSet('showConfirmationModal', false)
            ->assertSet('pendingRecipientCount', 0);

        $this->assertDatabaseCount('email_campaigns', 0);
        $this->assertDatabaseCount('email_campaign_recipients', 0);
        Queue::assertNothingPushed();
    }

    private function recipient(EmailCampaign $campaign, string $email, string $userUid): EmailCampaignRecipient
    {
        return EmailCampaignRecipient::create([
            'email_campaign_id' => $campaign->id,
            'user_uid' => $userUid,
            'email' => $email,
            'status' => EmailCampaignRecipient::STATUS_PENDING,
        ]);
    }

    private function successfulTransaction(User $buyer, Event $event, string $suffix): Transaction
    {
        return $this->transaction($buyer, $event, Cart::STATUS_SUCCESS, $suffix);
    }

    private function transaction(User $buyer, Event $event, string $status, string $suffix): Transaction
    {
        $harga = Harga::create([
            'uid' => $event->uid,
            'kategori' => 'Regular ' . $suffix,
            'qty' => 10,
            'sold_qty' => 1,
            'reserved_qty' => 0,
            'harga' => 50000,
            'status' => 'active',
        ]);

        $cart = Cart::create([
            'uid' => (string) Str::uuid(),
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'invoice' => 'INV-' . Str::upper(Str::random(8)),
            'status' => $status,
            'payment_type' => 'bank_transfer',
            'gross_amount' => 50000,
            'paid_at' => now(),
        ]);

        HargaCart::create([
            'uid' => $cart->uid,
            'harga_id' => $harga->id,
            'orderBy' => '1',
            'event_uid' => $event->uid,
            'quantity' => 1,
            'harga_ticket' => 50000,
            'kategori_harga' => $harga->kategori,
        ]);

        return Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $buyer->uid,
            'event_uid' => $event->uid,
            'amount' => '50000',
            'gross_amount' => 50000,
            'invoice' => $cart->invoice,
            'payment_type' => 'bank_transfer',
            'status_transaksi' => $status,
            'paid_at' => now(),
        ]);
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Email Blast User',
            'email' => fake()->unique()->safeEmail(),
            'role' => User::USER_ROLE,
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function event(User $owner, string $prefix = 'blast'): Event
    {
        $uid = (string) Str::uuid();

        return Event::create([
            'uid' => $uid,
            'user_uid' => $owner->uid,
            'event' => 'Email Blast Event ' . $prefix . ' ' . $uid,
            'alamat' => 'Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i'),
            'status' => 'active',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'deskripsi' => 'Event',
            'map' => 'https://example.test/map',
            'pajak' => 0,
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'email-blast-event-' . $prefix . '-' . Str::lower(Str::random(6)),
            'konfirmasi' => '1',
        ]);
    }
}
