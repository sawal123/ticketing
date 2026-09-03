<?php

namespace App\Services\Registrations;

use App\Models\Event;
use App\Models\EventRegistrationField;
use Illuminate\Support\Collection;

class TeamRosterValidator
{
    /**
     * Validate a team registration payload against the authoritative event and
     * its member-scope registration fields, and return a normalized structure.
     *
     * This service only VALIDATES and NORMALIZES. It never persists an
     * EventRegistration or EventRegistrationMember and never touches Cart /
     * transaction / payment state.
     *
     * The event and its registration fields are read from the database
     * (authoritative). event_uid / user_uid / registration_uid are never
     * accepted from the payload as authority.
     *
     * @return array{valid: bool, errors: array, data: array|null}
     */
    public function validateAndNormalize(Event $event, array $payload): array
    {
        $errors = [];
        $event = $this->authoritativeEvent($event);

        if ($event->registration_mode !== Event::REGISTRATION_MODE_TEAM) {
            $errors['event'][] = 'Roster tim hanya berlaku untuk event dengan mode pendaftaran tim.';

            return $this->invalid($errors);
        }

        $min = $event->team_min_members;
        $max = $event->team_max_members;

        if ($min === null || $max === null) {
            $errors['event'][] = 'Konfigurasi jumlah anggota tim belum lengkap.';

            return $this->invalid($errors);
        }

        $min = (int) $min;
        $max = (int) $max;

        if ($min < 1) {
            $errors['event'][] = 'Jumlah minimum anggota tim tidak valid.';
        }

        if ($max < $min) {
            $errors['event'][] = 'Jumlah maksimum anggota tidak boleh lebih kecil dari jumlah minimum.';
        }

        $teamName = is_string($payload['team_name'] ?? null) ? trim($payload['team_name']) : null;
        if ($teamName === null || $teamName === '') {
            $errors['team_name'][] = 'Nama tim wajib diisi.';
        } elseif (mb_strlen($teamName) > 255) {
            $errors['team_name'][] = 'Nama tim maksimal 255 karakter.';
        }

        $membersPayload = $payload['members'] ?? null;
        if (! is_array($membersPayload)) {
            $errors['members'][] = 'Daftar anggota tim tidak valid.';

            return $this->invalid($errors);
        }

        $count = count($membersPayload);
        if ($count < $min) {
            $errors['members'][] = "Jumlah anggota minimal {$min}.";
        }

        if ($count > $max) {
            $errors['members'][] = "Jumlah anggota maksimal {$max}.";
        }

        $captainCount = 0;
        foreach ($membersPayload as $rawMember) {
            if (is_array($rawMember) && ! empty($rawMember['is_captain'])) {
                $captainCount++;
            }
        }
        if ($captainCount === 0) {
            $errors['captain'][] = 'Tentukan satu kapten tim.';
        } elseif ($captainCount > 1) {
            $errors['captain'][] = 'Hanya boleh ada satu kapten tim.';
        }

        $memberFields = EventRegistrationField::where('event_uid', $event->uid)
            ->where('scope', 'member')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $fieldMap = [];
        foreach ($memberFields as $field) {
            $fieldMap[(string) $field->id] = $field;
        }

        $normalizedMembers = [];
        foreach ($membersPayload as $index => $rawMember) {
            $normalizedMember = $this->normalizeMember($event, $memberFields, $fieldMap, $rawMember, $index, $errors);
            $normalizedMembers[] = $normalizedMember;
        }

        if ($errors) {
            return $this->invalid($errors);
        }

        return [
            'valid' => true,
            'errors' => [],
            'data' => [
                'event_uid' => $event->uid,
                'registration_mode' => $event->registration_mode,
                'team_name' => $teamName,
                'members' => $normalizedMembers,
            ],
        ];
    }

    private function authoritativeEvent(Event $event): Event
    {
        return Event::query()
            ->where('uid', $event->uid)
            ->firstOrFail();
    }

    /**
     * @param  Collection<int, EventRegistrationField>  $memberFields
     * @param  array<string, EventRegistrationField>  $fieldMap
     */
    private function normalizeMember(Event $event, iterable $memberFields, array $fieldMap, mixed $rawMember, int $index, array &$errors): array
    {
        $isCaptain = is_array($rawMember) && ! empty($rawMember['is_captain']);
        $sortOrder = $index + 1;

        if (! is_array($rawMember)) {
            $errors["members.{$index}"][] = 'Data anggota tidak valid.';

            return [
                'is_captain' => false,
                'sort_order' => $sortOrder,
                'answers' => [],
            ];
        }

        return [
            'is_captain' => $isCaptain,
            'sort_order' => $sortOrder,
            'answers' => $this->normalizeMemberAnswers($event, $memberFields, $fieldMap, $rawMember['answers'] ?? [], $index, $errors),
        ];
    }

    /**
     * @param  Collection<int, EventRegistrationField>  $memberFields
     * @param  array<string, EventRegistrationField>  $fieldMap
     */
    private function normalizeMemberAnswers(Event $event, iterable $memberFields, array $fieldMap, mixed $answersPayload, int $index, array &$errors): array
    {
        $normalized = [];

        if ($answersPayload === null) {
            $answersPayload = [];
        }

        if (! is_array($answersPayload)) {
            $errors["members.{$index}.answers"][] = 'Jawaban anggota harus berupa data.';

            return $normalized;
        }

        $provided = [];
        foreach ($answersPayload as $rawId => $value) {
            $fieldId = (string) $rawId;

            if (! isset($fieldMap[$fieldId])) {
                $this->recordUnknownFieldError($event, $fieldId, $index, $errors);

                continue;
            }

            $provided[$fieldId] = $value;
        }

        foreach ($memberFields as $field) {
            $fieldId = (string) $field->id;
            $has = array_key_exists($fieldId, $provided);
            $value = $has ? $provided[$fieldId] : null;

            if ($this->isBlank($value)) {
                if ($field->is_required) {
                    $errors["members.{$index}.answers.{$fieldId}"][] = 'Field "'.$field->label.'" wajib diisi.';
                }

                continue;
            }

            $result = $this->normalizeValue($field, $value);
            if ($result['ok'] === false) {
                $errors["members.{$index}.answers.{$fieldId}"][] = $result['message'];

                continue;
            }

            $normalized[(int) $field->id] = $result['value'];
        }

        return $normalized;
    }

    private function recordUnknownFieldError(Event $event, string $fieldId, int $index, array &$errors): void
    {
        $key = "members.{$index}.answers.{$fieldId}";

        if (! ctype_digit($fieldId)) {
            $errors[$key][] = 'Field tidak dikenal.';

            return;
        }

        $other = EventRegistrationField::find((int) $fieldId);
        if ($other === null) {
            $errors[$key][] = 'Field tidak dikenal.';

            return;
        }

        if ((string) $other->event_uid === (string) $event->uid && $other->scope === 'registration') {
            $errors[$key][] = 'Field scope registration tidak dapat digunakan sebagai jawaban anggota tim.';

            return;
        }

        if ((string) $other->event_uid !== (string) $event->uid) {
            $errors[$key][] = 'Field bukan milik event ini.';

            return;
        }

        $errors[$key][] = 'Field tidak dikenal.';
    }

    /**
     * @return array{ok: bool, value?: mixed, message?: string}
     */
    private function normalizeValue(EventRegistrationField $field, mixed $value): array
    {
        switch ($field->type) {
            case 'number':
                if (! is_numeric($value)) {
                    return ['ok' => false, 'message' => 'Field "'.$field->label.'" harus berupa angka.'];
                }

                return ['ok' => true, 'value' => $value + 0];

            case 'select':
                $options = array_values(array_map('strval', $field->options ?? []));
                if (! in_array((string) $value, $options, true)) {
                    return ['ok' => false, 'message' => 'Pilihan untuk field "'.$field->label.'" tidak valid.'];
                }

                return ['ok' => true, 'value' => (string) $value];

            case 'textarea':
            case 'text':
                if (! is_string($value)) {
                    return ['ok' => false, 'message' => 'Field "'.$field->label.'" harus berupa teks.'];
                }

                return ['ok' => true, 'value' => trim(strip_tags($value))];

            default:
                return ['ok' => true, 'value' => trim(strip_tags((string) $value))];
        }
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        return trim((string) $value) === '';
    }

    /**
     * @return array{valid: false, errors: array, data: null}
     */
    private function invalid(array $errors): array
    {
        return [
            'valid' => false,
            'errors' => $errors,
            'data' => null,
        ];
    }
}
