<?php

namespace App\Services\Registrations;

use App\Models\Cart;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventRegistrationField;
use App\Models\EventRegistrationMember;
use App\Models\HargaCart;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutRegistrationService
{
    public function __construct(private readonly TeamRosterValidator $teamRosterValidator) {}

    public function persist(Cart $cart, array $payload): ?EventRegistration
    {
        $event = Event::query()->where('uid', $cart->event_uid)->firstOrFail();

        if ($event->registration_mode === Event::REGISTRATION_MODE_TICKETING) {
            return null;
        }

        if ($cart->hasActivePaymentLink()) {
            throw ValidationException::withMessages([
                'registration' => 'Data pendaftaran tidak dapat diubah setelah link pembayaran aktif.',
            ]);
        }

        $this->assertSingleRegistrationQuantity($cart);
        $answers = $this->normalizeRegistrationAnswers($event, $payload['registration_answers'] ?? null);
        $roster = $event->registration_mode === Event::REGISTRATION_MODE_TEAM
            ? $this->normalizeTeamRoster($event, $payload)
            : null;

        $registration = EventRegistration::query()->firstOrNew(['cart_uid' => $cart->uid]);
        $registration->fill([
            'uid' => $registration->uid ?: (string) Str::uuid(),
            'cart_uid' => $cart->uid,
            'invoice' => $cart->invoice,
            'event_uid' => $cart->event_uid,
            'user_uid' => $cart->user_uid,
            'registration_mode' => $event->registration_mode,
            'status' => EventRegistration::STATUS_PENDING,
            'team_name' => $roster['team_name'] ?? null,
            'answers' => $answers,
        ]);
        $registration->save();

        if ($event->registration_mode === Event::REGISTRATION_MODE_TEAM) {
            $registration->members()->delete();

            foreach ($roster['members'] as $member) {
                EventRegistrationMember::create([
                    'uid' => (string) Str::uuid(),
                    'registration_uid' => $registration->uid,
                    'is_captain' => $member['is_captain'],
                    'sort_order' => $member['sort_order'],
                    'answers' => $member['answers'],
                ]);
            }
        } else {
            $registration->members()->delete();
        }

        return $registration;
    }

    public function syncStatus(Cart $cart, string $status): void
    {
        if (! Schema::hasTable('event_registrations')) {
            return;
        }

        EventRegistration::query()
            ->where('cart_uid', $cart->uid)
            ->update(['status' => $status]);
    }

    private function assertSingleRegistrationQuantity(Cart $cart): void
    {
        $quantity = (int) HargaCart::query()->where('uid', $cart->uid)->sum('quantity');

        if ($quantity !== 1) {
            throw ValidationException::withMessages([
                'registration' => 'Pendaftaran individual atau tim hanya dapat checkout satu tiket.',
            ]);
        }
    }

    private function normalizeTeamRoster(Event $event, array $payload): array
    {
        $result = $this->teamRosterValidator->validateAndNormalize($event, [
            'team_name' => $payload['team_name'] ?? null,
            'members' => $payload['members'] ?? null,
        ]);

        if (! $result['valid']) {
            throw ValidationException::withMessages($result['errors']);
        }

        return $result['data'];
    }

    private function normalizeRegistrationAnswers(Event $event, mixed $answersPayload): array
    {
        $errors = [];
        $answersPayload ??= [];

        if (! is_array($answersPayload)) {
            throw ValidationException::withMessages([
                'registration_answers' => 'Jawaban pendaftaran harus berupa data.',
            ]);
        }

        $fields = EventRegistrationField::query()
            ->where('event_uid', $event->uid)
            ->where('scope', 'registration')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $fieldMap = $fields->keyBy(fn (EventRegistrationField $field) => (string) $field->id);
        $provided = [];

        foreach ($answersPayload as $rawId => $value) {
            $fieldId = (string) $rawId;
            if (! isset($fieldMap[$fieldId])) {
                $errors["registration_answers.{$fieldId}"][] = $this->unknownFieldMessage($event, $fieldId);

                continue;
            }

            $provided[$fieldId] = $value;
        }

        $normalized = [];
        foreach ($fields as $field) {
            $fieldId = (string) $field->id;
            $value = $provided[$fieldId] ?? null;

            if ($this->isBlank($value)) {
                if ($field->is_required) {
                    $errors["registration_answers.{$fieldId}"][] = 'Field "'.$field->label.'" wajib diisi.';
                }

                continue;
            }

            $result = $this->normalizeValue($field, $value);
            if (! $result['ok']) {
                $errors["registration_answers.{$fieldId}"][] = $result['message'];

                continue;
            }

            $normalized[(int) $field->id] = $result['value'];
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    private function unknownFieldMessage(Event $event, string $fieldId): string
    {
        if (! ctype_digit($fieldId)) {
            return 'Field tidak dikenal.';
        }

        $field = EventRegistrationField::query()->find((int) $fieldId);
        if (! $field) {
            return 'Field tidak dikenal.';
        }

        if ((string) $field->event_uid !== (string) $event->uid) {
            return 'Field bukan milik event ini.';
        }

        if ($field->scope === 'member') {
            return 'Field scope member tidak dapat digunakan sebagai jawaban pendaftaran.';
        }

        return 'Field tidak dikenal.';
    }

    private function normalizeValue(EventRegistrationField $field, mixed $value): array
    {
        return match ($field->type) {
            'number' => is_numeric($value)
                ? ['ok' => true, 'value' => $value + 0]
                : ['ok' => false, 'message' => 'Field "'.$field->label.'" harus berupa angka.'],
            'select' => (is_string($value) || is_int($value) || is_float($value))
                && in_array((string) $value, array_values(array_map('strval', $field->options ?? [])), true)
                ? ['ok' => true, 'value' => (string) $value]
                : ['ok' => false, 'message' => 'Pilihan untuk field "'.$field->label.'" tidak valid.'],
            'text', 'textarea' => is_string($value)
                ? ['ok' => true, 'value' => trim(strip_tags($value))]
                : ['ok' => false, 'message' => 'Field "'.$field->label.'" harus berupa teks.'],
            default => ['ok' => false, 'message' => 'Tipe field tidak valid.'],
        };
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
