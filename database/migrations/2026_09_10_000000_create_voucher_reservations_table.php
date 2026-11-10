<?php

use App\Models\Cart;
use App\Models\VoucherReservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voucher_reservations')) {
            return;
        }

        Schema::create('voucher_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->string('voucher_uid')->nullable();
            $table->string('cart_uid');
            $table->string('invoice')->nullable();
            $table->string('code');
            $table->string('status')->default(VoucherReservation::STATUS_ACTIVE);
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();

            $table->unique('cart_uid', 'voucher_reservations_cart_uid_unique');
            $table->index(['voucher_id', 'status'], 'voucher_reservations_voucher_status_idx');
            $table->index(['code', 'status'], 'voucher_reservations_code_status_idx');
        });

        $reservedByVoucher = [];

        DB::table('cart_vouchers')
            ->join('carts', 'carts.uid', '=', 'cart_vouchers.uid')
            ->whereIn('carts.status', Cart::ACTIVE_RESERVATION_STATUSES)
            ->whereNull('carts.deleted_at')
            ->whereNull('carts.reservation_released_at')
            ->whereNotNull('cart_vouchers.code')
            ->where('cart_vouchers.code', '!=', '')
            ->select([
                'cart_vouchers.id as cart_voucher_id',
                'cart_vouchers.uid_vouchers as voucher_uid',
                'cart_vouchers.uid as cart_uid',
                'cart_vouchers.event_uid',
                'cart_vouchers.code',
                'carts.invoice',
            ])
            ->orderBy('cart_vouchers.id')
            ->chunkById(500, function ($rows) use (&$reservedByVoucher) {
                foreach ($rows as $row) {
                    $voucherQuery = DB::table('vouchers')->where('event_uid', $row->event_uid);
                    $voucher = filled($row->voucher_uid)
                        ? (clone $voucherQuery)->where('uid', $row->voucher_uid)->first()
                        : null;
                    $voucher ??= (clone $voucherQuery)->where('code', $row->code)->first();

                    if (! $voucher || $voucher->status !== 'active') {
                        continue;
                    }

                    $reserved = $reservedByVoucher[$voucher->id] ?? 0;
                    $limited = (int) $voucher->limit > 0;
                    $recordedUsage = DB::table('voucher_usages')
                        ->where(function ($query) use ($voucher) {
                            $query->where('voucher_id', $voucher->id)
                                ->orWhere('voucher_uid', $voucher->uid);
                        })
                        ->count();
                    $committedUsage = max((int) $voucher->digunakan, $recordedUsage);

                    if ($limited && ($committedUsage + $reserved) >= (int) $voucher->limit) {
                        continue;
                    }

                    $inserted = DB::table('voucher_reservations')->insertOrIgnore([
                        'voucher_id' => $voucher->id,
                        'voucher_uid' => $voucher->uid,
                        'cart_uid' => $row->cart_uid,
                        'invoice' => $row->invoice,
                        'code' => $voucher->code,
                        'status' => VoucherReservation::STATUS_ACTIVE,
                        'reserved_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($inserted) {
                        $reservedByVoucher[$voucher->id] = $reserved + 1;
                    }
                }
            }, 'cart_vouchers.id', 'cart_voucher_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_reservations');
    }
};
