<?php

namespace App\Services;

use App\Rules\SecureImageUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SecureImageStorage
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'phar', 'php5', 'php7', 'php8'];

    private const MAX_BYTES = 2 * 1024 * 1024;

    private const MAX_PIXELS = 40_000_000;

    /**
     * Reusable validation rules for every public image upload.
     *
     * @return array<int, mixed>
     */
    public static function rules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
            new SecureImageUpload,
        ];
    }

    public static function validationError(mixed $value): ?string
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return 'File gambar tidak valid.';
        }

        $originalName = str_replace('\\', '/', $value->getClientOriginalName());
        $baseName = basename($originalName);
        $segments = explode('.', strtolower($baseName));

        if ($baseName === '' || $baseName !== $originalName || count($segments) !== 2) {
            return 'Nama file gambar harus memakai satu ekstensi yang aman.';
        }

        $extension = end($segments);
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return 'Ekstensi gambar hanya boleh jpg, jpeg, png, atau webp.';
        }

        foreach ($segments as $segment) {
            if (in_array($segment, self::BLOCKED_EXTENSIONS, true)) {
                return 'Ekstensi file executable tidak diizinkan.';
            }
        }

        $path = $value->getRealPath();
        $size = $value->getSize();
        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return 'File gambar tidak dapat dibaca.';
        }

        if (! is_int($size) || $size < 1 || $size > self::MAX_BYTES) {
            return 'Ukuran gambar maksimal 2 MB.';
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return 'File gambar tidak dapat dibaca.';
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            return 'MIME gambar hanya boleh JPEG, PNG, atau WEBP.';
        }

        $info = @getimagesizefromstring($contents);
        $imageType = $info[2] ?? null;

        if ($info === false
            || ! in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || ($info[0] * $info[1]) > self::MAX_PIXELS) {
            return 'Isi atau dimensi gambar tidak valid.';
        }

        if (self::hasExecutableTrailingPayload($contents, $imageType)) {
            return 'Gambar mengandung payload executable.';
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            return 'Gambar gagal didekode.';
        }

        imagedestroy($image);

        return null;
    }

    /**
     * Look only at bytes placed after the logical end of the image.
     *
     * Searching the complete compressed binary for a generic "<?" sequence
     * causes false positives because those two bytes can naturally occur in a
     * valid JPEG/PNG/WEBP stream. The stored image is still decoded and
     * re-encoded, so metadata and compressed input bytes are never preserved.
     */
    private static function hasExecutableTrailingPayload(string $contents, int $imageType): bool
    {
        $trailing = self::trailingBytes($contents, $imageType);

        if ($trailing === '') {
            return false;
        }

        return preg_match('/<\?(?:php\b|=|[a-z_\\\\])|__HALT_COMPILER\s*\(/i', $trailing) === 1;
    }

    private static function trailingBytes(string $contents, int $imageType): string
    {
        if ($imageType === IMAGETYPE_JPEG) {
            $end = strrpos($contents, "\xFF\xD9");

            return $end === false ? $contents : substr($contents, $end + 2);
        }

        if ($imageType === IMAGETYPE_PNG) {
            $marker = "IEND\xAE\x42\x60\x82";
            $end = strrpos($contents, $marker);

            return $end === false ? $contents : substr($contents, $end + strlen($marker));
        }

        if ($imageType === IMAGETYPE_WEBP
            && strlen($contents) >= 12
            && substr($contents, 0, 4) === 'RIFF'
            && substr($contents, 8, 4) === 'WEBP') {
            $size = unpack('Vsize', substr($contents, 4, 4));
            $declaredLength = 8 + (int) ($size['size'] ?? 0);

            if ($declaredLength >= 12 && $declaredLength <= strlen($contents)) {
                return substr($contents, $declaredLength);
            }
        }

        return $contents;
    }

    /**
     * Decode the uploaded image and write a fresh server-generated WEBP.
     *
     * @return string Relative path on the public disk.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        if (($error = self::validationError($file)) !== null) {
            throw new RuntimeException($error);
        }

        $directory = $this->safeDirectory($directory);
        $contents = file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('Gambar gagal didekode.');
        }

        $extension = 'webp';
        ob_start();

        if (function_exists('imagewebp')) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            $encoded = imagewebp($image, null, 85);
        } else {
            $extension = 'jpg';
            $flattened = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($flattened, 255, 255, 255);
            imagefill($flattened, 0, 0, $white);
            imagecopy($flattened, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $encoded = imagejpeg($flattened, null, 88);
            imagedestroy($flattened);
        }

        $safeImage = ob_get_clean();
        imagedestroy($image);

        if (! $encoded || ! is_string($safeImage) || $safeImage === '') {
            throw new RuntimeException('Gambar gagal di-encode ulang.');
        }

        $path = $directory.'/'.Str::uuid().'.'.$extension;
        if (! Storage::disk('public')->put($path, $safeImage)) {
            throw new RuntimeException('Gambar gagal disimpan.');
        }

        return $path;
    }

    public function storeBasename(UploadedFile $file, string $directory): string
    {
        return basename($this->store($file, $directory));
    }

    /**
     * Delete only a basename inside the expected public-disk directory.
     */
    public function delete(string $directory, ?string $storedValue): void
    {
        if ($storedValue === null || trim($storedValue) === '') {
            return;
        }

        $directory = $this->safeDirectory($directory);
        $baseName = basename(str_replace('\\', '/', $storedValue));

        if ($baseName === '' || in_array($baseName, ['.', '..'], true)) {
            return;
        }

        Storage::disk('public')->delete($directory.'/'.$baseName);
    }

    private function safeDirectory(string $directory): string
    {
        $directory = trim(str_replace('\\', '/', $directory), '/');

        if ($directory === ''
            || preg_match('#^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$#', $directory) !== 1) {
            throw new RuntimeException('Direktori penyimpanan gambar tidak valid.');
        }

        return $directory;
    }
}
