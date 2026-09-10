<?php

namespace App\Support;

final class DownsizeVisionImage
{
    public const MAX_EDGE = 1024;

    public const JPEG_QUALITY = 75;

    /**
     * Shrink and JPEG-compress a photo before it is sent to a vision API.
     *
     * @return array{binary: string, media_type: string}
     */
    public static function handle(string $binary, string $mediaType): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return ['binary' => $binary, 'media_type' => $mediaType];
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return ['binary' => $binary, 'media_type' => $mediaType];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $needsScale = $width > self::MAX_EDGE || $height > self::MAX_EDGE;
        $alreadySmallJpeg = $mediaType === 'image/jpeg' && ! $needsScale && strlen($binary) < 180_000;

        if ($alreadySmallJpeg) {
            imagedestroy($image);

            return ['binary' => $binary, 'media_type' => $mediaType];
        }

        $target = $image;

        if ($needsScale) {
            $scale = self::MAX_EDGE / max($width, $height);
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
            imagedestroy($image);

            if ($resized === false) {
                return ['binary' => $binary, 'media_type' => $mediaType];
            }

            $target = $resized;
        }

        ob_start();
        imagejpeg($target, null, self::JPEG_QUALITY);
        $jpeg = (string) ob_get_clean();
        imagedestroy($target);

        return [
            'binary' => $jpeg !== '' ? $jpeg : $binary,
            'media_type' => $jpeg !== '' ? 'image/jpeg' : $mediaType,
        ];
    }
}
