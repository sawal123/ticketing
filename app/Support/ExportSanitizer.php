<?php

namespace App\Support;

class ExportSanitizer
{
    public static function csvCell(mixed $value): string|int|float|null
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = (string) $value;
        $value = str_replace(["\r", "\n"], ' ', $value);
        $value = mb_substr($value, 0, 1000);

        $trimmed = ltrim($value);
        $startsWithDangerousRawPrefix = $value !== '' && preg_match('/^[=+\-@\t]/', $value);

        if ($startsWithDangerousRawPrefix || ($trimmed !== '' && preg_match('/^[=+\-@]/', $trimmed))) {
            return "'".$value;
        }

        return $value;
    }

    public static function csvRow(array $row): array
    {
        return array_map([self::class, 'csvCell'], $row);
    }
}
