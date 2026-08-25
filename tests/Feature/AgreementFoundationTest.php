<?php

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class AgreementFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_agreement_mou_draft_can_be_created_manually(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $agreement = $this->agreement($tenant, $event);

        $this->assertNotNull($agreement->id);
        $this->assertSame(1, $agreement->version);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertSame($event->uid, $agreement->event->uid);
        $this->assertSame($tenant->uid, $agreement->tenant->uid);
    }

    public function test_create_draft_for_event_uses_event_owner_as_tenant_and_keeps_placeholders_null(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $agreement = Agreement::createDraftForEvent($event, $tenant->uid);

        $this->assertNotEmpty($agreement->uid);
        $this->assertSame($event->uid, $agreement->event_uid);
        $this->assertSame($event->user_uid, $agreement->tenant_user_uid);
        $this->assertSame($tenant->uid, $agreement->created_by);
        $this->assertSame(Agreement::TYPE_MOU, $agreement->type);
        $this->assertSame(1, $agreement->version);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertNull($agreement->event_snapshot);
        $this->assertNull($agreement->party_snapshot);
        $this->assertNull($agreement->bank_snapshot);
        $this->assertNull($agreement->document_snapshot);
        $this->assertNull($agreement->commercial_snapshot);
        $this->assertNull($agreement->document_number);
        $this->assertNull($agreement->template_version);
        $this->assertNull($agreement->unsigned_pdf_path);
        $this->assertNull($agreement->signed_pdf_path);
        $this->assertNull($agreement->privy_document_id);
        $this->assertNull($agreement->privy_status);
        $this->assertNull($agreement->privy_reference);
        $this->assertNull($agreement->sent_to_privy_at);
        $this->assertNull($agreement->signed_at);
        $this->assertNull($agreement->completed_at);
    }

    public function test_create_draft_for_event_is_idempotent_for_mou_version_one(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $first = Agreement::createDraftForEvent($event, $tenant->uid);
        $second = Agreement::createDraftForEvent($event, 'another-actor');

        $this->assertSame($first->id, $second->id);
        $this->assertSame($tenant->uid, $second->created_by);
        $this->assertDatabaseCount('agreements', 1);
    }

    public function test_snapshot_fields_are_cast_to_array(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $agreement = $this->agreement($tenant, $event, [
            'event_snapshot' => ['event' => 'Konser A'],
            'party_snapshot' => ['tenant' => 'PT Maju'],
            'bank_snapshot' => ['bank' => 'BCA'],
            'document_snapshot' => ['number' => 'DOC-1'],
            'commercial_snapshot' => ['fee' => 10],
        ])->fresh();

        $this->assertSame(['event' => 'Konser A'], $agreement->event_snapshot);
        $this->assertSame(['tenant' => 'PT Maju'], $agreement->party_snapshot);
        $this->assertSame(['bank' => 'BCA'], $agreement->bank_snapshot);
        $this->assertSame(['number' => 'DOC-1'], $agreement->document_snapshot);
        $this->assertSame(['fee' => 10], $agreement->commercial_snapshot);
    }

    public function test_event_isolation_keeps_agreements_scoped_to_each_event(): void
    {
        [$tenant, $eventA] = $this->tenantWithEvent(['email' => 'agreement-a@example.test']);
        $eventB = $this->event($tenant, [
            'event' => 'Agreement Event B',
            'slug' => 'agreement-event-b-'.Str::lower(Str::random(8)),
        ]);

        $agreementA = $this->agreement($tenant, $eventA);
        $agreementB = $this->agreement($tenant, $eventB, ['version' => 2]);

        $this->assertTrue($eventA->agreements->contains($agreementA));
        $this->assertFalse($eventA->agreements->contains($agreementB));
        $this->assertTrue($eventB->agreements->contains($agreementB));
        $this->assertFalse($eventB->agreements->contains($agreementA));
        $this->assertSame($tenant->uid, $agreementA->tenant->uid);
        $this->assertSame($tenant->uid, $agreementB->tenant->uid);
    }

    public function test_agreement_rejects_mismatched_event_owner_and_tenant_on_create(): void
    {
        [$tenantA] = $this->tenantWithEvent(['email' => 'agreement-create-a@example.test']);
        [$tenantB, $eventB] = $this->tenantWithEvent(['email' => 'agreement-create-b@example.test']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Agreement tenant must match the event owner.');

        $this->agreement($tenantA, $eventB, [
            'tenant_user_uid' => $tenantA->uid,
        ]);
    }

    public function test_draft_agreement_rejects_mismatched_event_owner_and_tenant_on_update(): void
    {
        [$tenantA, $eventA] = $this->tenantWithEvent(['email' => 'agreement-update-a@example.test']);
        [, $eventB] = $this->tenantWithEvent(['email' => 'agreement-update-b@example.test']);

        $agreement = $this->agreement($tenantA, $eventA);

        try {
            $agreement->update(['event_uid' => $eventB->uid]);
            $this->fail('Expected mismatched event owner and tenant update to fail.');
        } catch (LogicException $exception) {
            $this->assertSame('Agreement tenant must match the event owner.', $exception->getMessage());
        }

        $agreement->refresh();

        $this->assertSame($eventA->uid, $agreement->event_uid);
        $this->assertSame($tenantA->uid, $agreement->tenant_user_uid);
    }

    public function test_same_event_and_type_can_have_different_versions_but_not_duplicate_version(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $this->agreement($tenant, $event, [
            'version' => 1,
        ]);

        $this->agreement($tenant, $event, [
            'version' => 2,
        ]);

        $this->expectException(QueryException::class);

        $this->agreement($tenant, $event, [
            'version' => 1,
        ]);
    }

    public function test_status_helpers_reflect_current_status(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $draft = $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_DRAFT,
        ]);

        $ready = $this->agreement($tenant, $event, [
            'version' => 2,
            'status' => Agreement::STATUS_READY,
        ]);

        $completed = $this->agreement($tenant, $event, [
            'version' => 3,
            'status' => Agreement::STATUS_COMPLETED,
        ]);

        $this->assertTrue($draft->isDraft());
        $this->assertFalse($draft->isReady());
        $this->assertFalse($draft->isCompleted());

        $this->assertTrue($ready->isReady());
        $this->assertFalse($ready->isDraft());
        $this->assertFalse($ready->isCompleted());

        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($completed->isDraft());
        $this->assertFalse($completed->isReady());
    }

    public function test_completed_agreement_is_immutable_and_cannot_be_deleted(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $agreement = $this->agreement($tenant, $event, [
            'document_number' => 'MOU-001',
            'status' => Agreement::STATUS_COMPLETED,
            'version' => 1,
            'event_snapshot' => ['event' => 'Frozen'],
            'signed_pdf_path' => 'private/agreements/signed.pdf',
        ]);

        try {
            $agreement->update([
                'document_number' => 'MOU-002',
                'version' => 2,
                'event_snapshot' => ['event' => 'Changed'],
                'signed_pdf_path' => 'private/agreements/changed.pdf',
            ]);
            $this->fail('Expected completed agreement update to fail.');
        } catch (LogicException $exception) {
            $this->assertSame('Completed agreement is immutable.', $exception->getMessage());
        }

        $agreement->refresh();

        $this->assertSame('MOU-001', $agreement->document_number);
        $this->assertSame(1, $agreement->version);
        $this->assertSame(['event' => 'Frozen'], $agreement->event_snapshot);
        $this->assertSame('private/agreements/signed.pdf', $agreement->signed_pdf_path);

        try {
            $agreement->delete();
            $this->fail('Expected completed agreement delete to fail.');
        } catch (LogicException $exception) {
            $this->assertSame('Completed agreement cannot be deleted.', $exception->getMessage());
        }

        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'status' => Agreement::STATUS_COMPLETED,
        ]);
    }

    public function test_agreement_can_transition_to_completed_but_stale_instance_cannot_save_quietly_afterwards(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $agreement = $this->agreement($tenant, $event, [
            'document_number' => 'MOU-DRAFT',
            'status' => Agreement::STATUS_DRAFT,
        ]);

        $staleAgreement = Agreement::findOrFail($agreement->id);
        $freshAgreement = Agreement::findOrFail($agreement->id);

        $freshAgreement->update([
            'status' => Agreement::STATUS_COMPLETED,
            'document_number' => 'MOU-COMPLETE',
        ]);

        try {
            $staleAgreement->document_number = 'MOU-SHOULD-FAIL';
            $staleAgreement->saveQuietly();
            $this->fail('Expected stale quiet save to fail once the agreement is completed.');
        } catch (LogicException $exception) {
            $this->assertSame('Completed agreement is immutable.', $exception->getMessage());
        }

        $agreement->refresh();

        $this->assertSame(Agreement::STATUS_COMPLETED, $agreement->status);
        $this->assertSame('MOU-COMPLETE', $agreement->document_number);
    }

    public function test_completed_agreement_cannot_be_deleted_quietly_even_from_stale_instance(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $agreement = $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_DRAFT,
        ]);

        $staleAgreement = Agreement::findOrFail($agreement->id);
        Agreement::findOrFail($agreement->id)->update([
            'status' => Agreement::STATUS_COMPLETED,
        ]);

        try {
            $staleAgreement->deleteQuietly();
            $this->fail('Expected stale quiet delete to fail once the agreement is completed.');
        } catch (LogicException $exception) {
            $this->assertSame('Completed agreement cannot be deleted.', $exception->getMessage());
        }

        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'status' => Agreement::STATUS_COMPLETED,
        ]);
    }

    public function test_draft_agreement_remains_editable(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $agreement = $this->agreement($tenant, $event, [
            'status' => Agreement::STATUS_DRAFT,
        ]);

        $agreement->update([
            'document_number' => 'DRAFT-001',
            'version' => 2,
            'event_snapshot' => ['event' => 'Editable'],
            'signed_pdf_path' => 'private/agreements/draft.pdf',
        ]);

        $agreement->refresh();

        $this->assertSame('DRAFT-001', $agreement->document_number);
        $this->assertSame(2, $agreement->version);
        $this->assertSame(['event' => 'Editable'], $agreement->event_snapshot);
        $this->assertSame('private/agreements/draft.pdf', $agreement->signed_pdf_path);
    }

    public function test_agreement_event_relation_still_resolves_after_event_is_soft_deleted(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();
        $agreement = $this->agreement($tenant, $event);

        $event->delete();
        $agreement->refresh();

        $this->assertNotNull($agreement->event);
        $this->assertTrue($agreement->event->trashed());
        $this->assertSame($event->uid, $agreement->event->uid);
        $this->assertSame($event->uid, $agreement->event_uid);
    }

    public function test_existing_event_does_not_require_agreement(): void
    {
        [, $event] = $this->tenantWithEvent();

        $this->assertCount(0, $event->agreements);
    }

    private function tenantWithEvent(array $userOverrides = []): array
    {
        $tenant = $this->user(array_merge(['role' => 'penyewa'], $userOverrides));
        $event = $this->event($tenant);

        return [$tenant, $event];
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Agreement User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'user',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function event(User $tenant, array $overrides = []): Event
    {
        $uid = (string) Str::uuid();

        return Event::create(array_merge([
            'uid' => $uid,
            'user_uid' => $tenant->uid,
            'event' => 'Agreement Event '.$uid,
            'alamat' => 'Istora Senayan, Jl. Pintu Satu Senayan, Jakarta Pusat, DKI Jakarta',
            'tanggal' => now()->addDay()->format('Y-m-d H:i:s'),
            'event_end' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'venue_name' => 'Istora Senayan',
            'venue_address' => 'Jl. Pintu Satu Senayan',
            'venue_city' => 'Jakarta Pusat',
            'venue_province' => 'DKI Jakarta',
            'status' => 'inactive',
            'cover' => 'cover.jpg',
            'fee' => 0,
            'pajak' => 0,
            'deskripsi' => 'Agreement event description',
            'map' => 'https://example.test/map',
            'start_sale' => now()->format('Y-m-d H:i:s'),
            'slug' => 'agreement-event-'.$uid,
            'konfirmasi' => null,
        ], $overrides));
    }

    private function agreement(User $tenant, Event $event, array $overrides = []): Agreement
    {
        return Agreement::create(array_merge([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
        ], $overrides));
    }
}
