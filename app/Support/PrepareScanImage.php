<?php

namespace App\Support;

use App\Exceptions\CouldNotReadPhotoException;
use Illuminate\Support\Facades\Process;
use Throwable;

final class PrepareScanImage
{
    /**
     * @return array{path: string, media_type: 'image/jpeg'|'image/png'|'image/gif'|'image/webp'}
     */
    public static function handle(string $absolutePath, string $mime): array
    {
        $mediaType = match (true) {
            in_array($mime, ['image/jpeg', 'image/jpg'], true) => 'image/jpeg',
            $mime === 'image/png' => 'image/png',
            $mime === 'image/gif' => 'image/gif',
            $mime === 'image/webp' => 'image/webp',
            in_array($mime, ['image/heic', 'image/heif'], true) => 'heic',
            default => self::guessFromPath($absolutePath),
        };

        if ($mediaType === 'heic') {
            return self::heicToJpeg($absolutePath);
        }

        if (! in_array($mediaType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            throw CouldNotReadPhotoException::make();
        }

        return [
            'path' => $absolutePath,
            'media_type' => $mediaType,
        ];
    }

    private static function guessFromPath(string $absolutePath): string
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'heic', 'heif' => 'heic',
            default => 'unknown',
        };
    }

    /**
     * @return array{path: string, media_type: 'image/jpeg'}
     */
    private static function heicToJpeg(string $absolutePath): array
    {
        $jpegPath = $absolutePath.'.jpg';

        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            try {
                $image = new \Imagick($absolutePath);
                $image->setImageFormat('jpeg');
                $image->writeImage($jpegPath);
                $image->clear();

                return ['path' => $jpegPath, 'media_type' => 'image/jpeg'];
            } catch (\ImagickException) {
                // Fall through to sips on macOS.
            }
        }

        if (PHP_OS_FAMILY === 'Darwin') {
            try {
                $result = Process::run(['sips', '-s', 'format', 'jpeg', $absolutePath, '--out', $jpegPath]);
            } catch (Throwable) {
                throw CouldNotReadPhotoException::make();
            }

            if ($result->successful() && is_file($jpegPath)) {
                return ['path' => $jpegPath, 'media_type' => 'image/jpeg'];
            }
        }

        throw CouldNotReadPhotoException::make();
    }
}
