<?php

namespace App\Services\Payments;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\PaymentGateway;
use App\Models\User;

class PaymentConfigurationAuditService
{
    private const FIELD_SCALES = [
        'default_fee_fixed' => 2,
        'default_fee_percent' => 4,
        'fee_fixed' => 2,
        'fee_percent' => 4,
    ];

    private const BOOLEAN_FIELDS = [
        'is_active',
        'payment_otp_enabled',
    ];

    private const STRING_FIELDS = [
        'fee_mode',
        'midtrans_code',
    ];

    private const ALLOWED_FIELDS = [
        'is_active',
        'fee_mode',
        'fee_fixed',
        'fee_percent',
        'default_fee_fixed',
        'default_fee_percent',
        'midtrans_code',
        'payment_otp_enabled',
    ];

    public function record(
        User $actor,
        string $actionKey,
        array $oldValues,
        array $newValues,
        ?Event $event = null,
        ?PaymentGateway $gateway = null,
        ?string $description = null
    ): ?ActivityLog {
        $oldValues = $this->normalizeValues($oldValues);
        $newValues = $this->normalizeValues($newValues);

        if ($oldValues === $newValues) {
            return null;
        }

        $request = request();

        return ActivityLog::create([
            'user_uid' => $actor->uid,
            'activity' => 'Payment Configuration',
            'audit_category' => 'payment',
            'action_key' => $actionKey,
            'event_uid' => $event?->uid,
            'payment_gateway_id' => $gateway?->id,
            'login_status' => 'Success',
            'description' => $description ?? $this->descriptionFor($actor, $event, $gateway),
            'impact_level' => 'Sensitif',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'location' => 'Unknown',
            'user_agent' => $request?->userAgent(),
            'device_id' => $request?->header('X-Device-Id') ?: $request?->cookie('device_id'),
            'session_id' => $request && $request->hasSession() ? $request->session()->getId() : null,
        ]);
    }

    public function normalizeValues(array $values): array
    {
        $normalized = [];

        foreach (self::ALLOWED_FIELDS as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            $normalized[$field] = $this->normalizeFieldValue($field, $values[$field]);
        }

        ksort($normalized);

        return $normalized;
    }

    public function changedKeys(array $oldValues, array $newValues): array
    {
        $oldValues = $this->normalizeValues($oldValues);
        $newValues = $this->normalizeValues($newValues);
        $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        $changed = [];

        foreach ($keys as $key) {
            if (($oldValues[$key] ?? null) !== ($newValues[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        sort($changed);

        return $changed;
    }

    private function normalizeFieldValue(string $field, mixed $value): mixed
    {
        if ($value === '') {
            $value = null;
        }

        if (in_array($field, self::BOOLEAN_FIELDS, true)) {
            return (bool) $value;
        }

        if (isset(self::FIELD_SCALES[$field])) {
            if ($value === null) {
                return null;
            }

            return number_format((float) $value, self::FIELD_SCALES[$field], '.', '');
        }

        if (in_array($field, self::STRING_FIELDS, true)) {
            return $value === null ? null : trim((string) $value);
        }

        return $value;
    }

    private function descriptionFor(User $actor, ?Event $event, ?PaymentGateway $gateway): string
    {
        if ($event && $gateway) {
            return sprintf(
                'Admin %s mengubah konfigurasi %s pada event %s.',
                $actor->name,
                $gateway->payment,
                $event->event
            );
        }

        if ($gateway) {
            return sprintf(
                'Admin %s mengubah default konfigurasi payment gateway %s.',
                $actor->name,
                $gateway->payment
            );
        }

        if ($event) {
            return sprintf(
                'Admin %s mengubah pengaturan pembayaran pada event %s.',
                $actor->name,
                $event->event
            );
        }

        return sprintf('Admin %s mengubah konfigurasi payment.', $actor->name);
    }
}
