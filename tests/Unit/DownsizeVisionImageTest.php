<?php

use App\Support\DownsizeVisionImage;

it('turns a large png into a smaller jpeg for the vision API', function () {
    if (! function_exists('imagecreatetruecolor')) {
        $this->markTestSkipped('GD is required');
    }

    $image = imagecreatetruecolor(1600, 1200);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();
    imagedestroy($image);

    $result = DownsizeVisionImage::handle($png, 'image/png');
    $jpeg = imagecreatefromstring($result['binary']);

    expect($result['media_type'])->toBe('image/jpeg')
        ->and($jpeg)->not->toBeFalse()
        ->and(imagesx($jpeg))->toBe(1024)
        ->and(imagesy($jpeg))->toBe(768);

    imagedestroy($jpeg);
});

it('leaves a small jpeg unchanged', function () {
    if (! function_exists('imagecreatetruecolor')) {
        $this->markTestSkipped('GD is required');
    }

    $image = imagecreatetruecolor(80, 80);
    ob_start();
    imagejpeg($image, null, 80);
    $jpeg = (string) ob_get_clean();
    imagedestroy($image);

    expect(DownsizeVisionImage::handle($jpeg, 'image/jpeg'))->toBe([
        'binary' => $jpeg,
        'media_type' => 'image/jpeg',
    ]);
});
