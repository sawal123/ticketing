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

        $agreement = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
        ]);

        $this->assertNotNull($agreement->id);
        $this->assertSame(1, $agreement->version);
        $this->assertSame(Agreement::STATUS_DRAFT, $agreement->status);
        $this->assertSame($event->uid, $agreement->event->uid);
        $this->assertSame($tenant->uid, $agreement->tenant->uid);
    }

    public function test_snapshot_fields_are_cast_to_array(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $agreement = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
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
        [, $eventB] = $this->tenantWithEvent(['email' => 'agreement-b@example.test']);

        $agreementA = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $eventA->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
        ]);

        $agreementB = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $eventB->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
        ]);

        $this->assertTrue($eventA->agreements->contains($agreementA));
        $this->assertFalse($eventA->agreements->contains($agreementB));
        $this->assertTrue($eventB->agreements->contains($agreementB));
        $this->assertFalse($eventB->agreements->contains($agreementA));
    }

    public function test_same_event_and_type_can_have_different_versions_but_not_duplicate_version(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
        ]);

        Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
            'version' => 2,
        ]);

        $this->expectException(QueryException::class);

        Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
        ]);
    }

    public function test_status_helpers_reflect_current_status(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $draft = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
            'status' => Agreement::STATUS_DRAFT,
        ]);

        $ready = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
            'version' => 2,
            'status' => Agreement::STATUS_READY,
        ]);

        $completed = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
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

        $agreement = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
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

    public function test_draft_agreement_remains_editable(): void
    {
        [$tenant, $event] = $this->tenantWithEvent();

        $agreement = Agreement::create([
            'uid' => (string) Str::uuid(),
            'event_uid' => $event->uid,
            'tenant_user_uid' => $tenant->uid,
            'type' => Agreement::TYPE_MOU,
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
}
