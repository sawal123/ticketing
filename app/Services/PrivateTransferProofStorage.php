<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PrivateTransferProofStorage
{
    private const DISK = 'local';

    private const DIRECTORY = 'private/penarikan-transfer-proofs';

    public function storeBasename(UploadedFile $file): string
    {
        if (($error = SecureImageStorage::validationError($file)) !== null) {
            throw new RuntimeException($error);
        }

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

        $basename = (string) Str::uuid().'.'.$extension;
        $path = self::DIRECTORY.'/'.$basename;

        if (! Storage::disk(self::DISK)->put($path, $safeImage)) {
            throw new RuntimeException('Gambar gagal disimpan.');
        }

        return $basename;
    }

    public function delete(?string $storedValue): void
    {
        $path = $this->path($storedValue);

        if ($path === null) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function exists(?string $storedValue): bool
    {
        $path = $this->path($storedValue);

        return $path !== null && Storage::disk(self::DISK)->exists($path);
    }

    public function readStream(string $storedValue)
    {
        return Storage::disk(self::DISK)->readStream($this->pathOrFail($storedValue));
    }

    public function mimeType(string $storedValue): ?string
    {
        return Storage::disk(self::DISK)->mimeType($this->pathOrFail($storedValue));
    }

    public function downloadName(string $penarikanUid, string $storedValue): string
    {
        $extension = strtolower(pathinfo($storedValue, PATHINFO_EXTENSION));
        $extension = $extension !== '' ? $extension : 'img';

        return 'bukti-transfer-'.$penarikanUid.'.'.$extension;
    }

    public function path(?string $storedValue): ?string
    {
        $baseName = $this->safeBaseName($storedValue);

        if ($baseName === null) {
            return null;
        }

        return self::DIRECTORY.'/'.$baseName;
    }

    private function pathOrFail(string $storedValue): string
    {
        $path = $this->path($storedValue);

        if ($path === null) {
            throw new RuntimeException('Path bukti transfer tidak valid.');
        }

        return $path;
    }

    private function safeBaseName(?string $storedValue): ?string
    {
        if ($storedValue === null || trim($storedValue) === '') {
            return null;
        }

        $baseName = basename(str_replace('\\', '/', $storedValue));

        if ($baseName === ''
            || in_array($baseName, ['.', '..'], true)
            || preg_match('/^[a-zA-Z0-9-]+\.(jpg|jpeg|png|webp)$/i', $baseName) !== 1) {
            return null;
        }

        return $baseName;
    }
}
