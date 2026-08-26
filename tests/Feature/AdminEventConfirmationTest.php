<?php

namespace Tests\Feature;

use App\Livewire\Admin\EventDetail;
use App\Livewire\Admin\EventIndex;
use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventBankAccount;
use App\Models\EventDocument;
use App\Models\EventOrganizer;
use App\Models\EventPaymentGateway;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEventConfirmationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Storage::fake('local');
        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('name');
            $table->string('email');
            $table->string('nomor');
            $table->string('birthday');
            $table->string('gender');
            $table->string('kota');
            $table->string('alamat');
            $table->string('role');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('events', function ($table) {
            $table->id();
            $table->string('category_id')->nullable();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event');
            $table->string('alamat');
            $table->string('tanggal');
            $table->string('status');
            $table->string('cover');
            $table->unsignedBigInteger('fee')->default(0);
            $table->text('deskripsi');
            $table->text('map')->nullable();
            $table->unsignedBigInteger('pajak')->default(0);
            $table->string('start_sale')->nullable();
            $table->string('slug')->nullable();
            $table->string('konfirmasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('talent', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('talent');
            $table->string('gambar')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });

        Schema::create('hargas', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('kategori');
            $table->unsignedInteger('qty')->default(0);
            $table->unsignedInteger('sold_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->unsignedBigInteger('harga')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('user_uid');
            $table->string('event_uid');
            $table->string('invoice')->nullable();
            $table->string('status');
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('internet_fee')->default(0);
            $table->unsignedBigInteger('pajak')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('harga_carts', function ($table) {
            $table->id();
            $table->string('uid');
            $table->unsignedBigInteger('harga_id')->nullable();
            $table->string('event_uid')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedBigInteger('harga_ticket')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_organizers', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('organizer_name');
            $table->string('responsible_name');
            $table->string('responsible_position');
            $table->string('phone');
            $table->string('email');
            $table->text('address');
            $table->timestamps();
        });

        Schema::create('event_bank_accounts', function ($table) {
            $table->id();
            $table->string('event_uid');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('bank_book_path')->nullable();
            $table->string('bank_book_original_name')->nullable();
            $table->string('bank_book_mime')->nullable();
            $table->string('status')->default('pending');
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('event_documents', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->date('document_date')->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('status')->default('pending');
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_gateways', function ($table) {
            $table->id();
            $table->string('payment');
            $table->string('category');
            $table->decimal('biaya', 15, 2)->default(0);
            $table->string('biaya_type')->default('fixed');
            $table->decimal('default_fee_fixed', 15, 2)->nullable();
            $table->decimal('default_fee_percent', 8, 4)->nullable();
            $table->string('midtrans_code')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('event_payment_gateways', function ($table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('payment_gateway_id');
            $table->boolean('is_active')->default(true);
            $table->string('fee_mode')->default(EventPaymentGateway::FEE_MODE_GLOBAL);
            $table->decimal('fee_fixed', 15, 2)->nullable();
            $table->decimal('fee_percent', 8, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('agreements', function ($table) {
            $table->id();
            $table->string('uid');
            $table->string('event_uid');
            $table->string('tenant_user_uid');
            $table->string('type')->default('mou');
            $table->string('document_number')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('DRAFT');
            $table->string('template_version')->nullable();
            $table->text('event_snapshot')->nullable();
            $table->text('party_snapshot')->nullable();
            $table->text('bank_snapshot')->nullable();
            $table->text('document_snapshot')->nullable();
            $table->text('commercial_snapshot')->nullable();
            $table->string('privy_document_id')->nullable();
            $table->string('privy_status')->nullable();
            $table->string('privy_reference')->nullable();
            $table->string('unsigned_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->string('signed_review_status')->nullable();
            $table->string('signed_verified_by')->nullable();
            $table->timestamp('signed_verified_at')->nullable();
            $table->text('signed_rejection_reason')->nullable();
            $table->timestamp('sent_to_privy_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_can_confirm_event_from_detail_page(): void
    {
        $admin = $this->makeAdmin();
        $event = $this->makePendingEvent();
        $this->seedActivationReadyState($event);

        Livewire::actingAs($admin)
            ->test(EventDetail::class, ['uid' => $event->uid])
            ->call('confirmEvent');

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'konfirmasi' => '1',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_confirm_event_from_event_table(): void
    {
        $admin = $this->makeAdmin();
        $event = $this->makePendingEvent(['uid' => 'pending-event-table']);
        $this->seedActivationReadyState($event);

        Livewire::actingAs($admin)
            ->test(EventIndex::class)
            ->call('confirmEvent', $event->uid);

        $this->assertDatabaseHas('events', [
            'uid' => $event->uid,
            'konfirmasi' => '1',
            'status' => 'active',
        ]);
    }

    private function makeAdmin(): User
    {
        return User::create([
            'uid' => 'admin-uid',
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'nomor' => '08123456789',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);
    }

    private function makePendingEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'category_id' => null,
            'uid' => 'pending-event',
            'user_uid' => 'owner-uid',
            'event' => 'Pending Event',
            'alamat' => 'Test Venue',
            'tanggal' => '2026-08-01 19:00',
            'status' => 'inactive',
            'cover' => 'pending-event.jpg',
            'fee' => 0,
            'deskripsi' => 'Test description',
            'map' => null,
            'pajak' => 0,
            'start_sale' => '2026-07-25 10:00',
            'slug' => 'pending-event',
            'konfirmasi' => null,
        ], $overrides));
    }

    private function seedActivationReadyState(Event $event): void
    {
        $bankPath = 'private/admin-confirm/'.$event->uid.'/bank-book.pdf';
        $letterPath = 'private/admin-confirm/'.$event->uid.'/organizer-letter.pdf';
        $agreementUid = 'agreement-'.$event->uid;
        $unsignedPath = 'private/admin-confirm/'.$agreementUid.'/unsigned.pdf';
        $signedPath = 'private/admin-confirm/'.$agreementUid.'/signed.pdf';

        EventOrganizer::create([
            'event_uid' => $event->uid,
            'organizer_name' => 'PT Confirm Organizer',
            'responsible_name' => 'Budi',
            'responsible_position' => 'Direktur',
            'phone' => '08123456789',
            'email' => 'organizer@example.test',
            'address' => 'Jl. Organizer',
        ]);

        EventBankAccount::create([
            'event_uid' => $event->uid,
            'bank_name' => 'Bank Confirm',
            'account_number' => '1234567890',
            'account_holder_name' => 'PT Confirm Organizer',
            'bank_book_path' => $bankPath,
            'bank_book_original_name' => 'bank-book.pdf',
            'bank_book_mime' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => 'admin-uid',
            'verified_at' => now()->subDay(),
        ]);

        EventDocument::create([
            'uid' => 'letter-'.$event->uid,
            'event_uid' => $event->uid,
            'document_type' => EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => 'DOC-CONFIRM-001',
            'document_date' => '2026-08-20',
            'original_name' => 'organizer-letter.pdf',
            'file_path' => $letterPath,
            'mime_type' => 'application/pdf',
            'status' => 'verified',
            'verified_by' => 'admin-uid',
            'verified_at' => now()->subDay(),
        ]);

        $gateway = PaymentGateway::create([
            'payment' => 'BCA Virtual Account',
            'category' => 'bank_transfer',
            'biaya' => 0,
            'biaya_type' => 'fixed',
            'default_fee_fixed' => 4000,
            'default_fee_percent' => 0,
            'midtrans_code' => 'bca_va',
            'icon' => 'bca.png',
            'is_active' => true,
            'slug' => 'bca-va-'.$event->uid,
        ]);

        EventPaymentGateway::create([
            'event_id' => $event->id,
            'payment_gateway_id' => $gateway->id,
            'is_active' => true,
            'fee_mode' => EventPaymentGateway::FEE_MODE_GLOBAL,
        ]);

        Agreement::create([
            'uid' => $agreementUid,
            'event_uid' => $event->uid,
            'tenant_user_uid' => $event->user_uid,
            'type' => Agreement::TYPE_MOU,
            'version' => 1,
            'status' => Agreement::STATUS_COMPLETED,
            'created_by' => $event->user_uid,
            'template_version' => 'mou-v1',
            'event_snapshot' => ['event_name' => $event->event],
            'party_snapshot' => ['organizer_name' => 'PT Confirm Organizer'],
            'bank_snapshot' => ['bank_name' => 'Bank Confirm'],
            'document_snapshot' => ['document_number' => 'DOC-CONFIRM-001'],
            'commercial_snapshot' => ['buyer_fee' => ['mode' => 'percent', 'value' => 10.0]],
            'document_number' => 'MOU-CONFIRM-001',
            'unsigned_pdf_path' => $unsignedPath,
            'signed_pdf_path' => $signedPath,
            'signed_review_status' => Agreement::SIGNED_REVIEW_VERIFIED,
            'signed_verified_by' => 'admin-uid',
            'signed_verified_at' => now()->subHour(),
            'signed_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);

        Storage::disk('local')->put($bankPath, '%PDF-1.4 bank');
        Storage::disk('local')->put($letterPath, '%PDF-1.4 letter');
        Storage::disk('local')->put($unsignedPath, '%PDF-1.4 unsigned');
        Storage::disk('local')->put($signedPath, '%PDF-1.4 signed');
    }
}
