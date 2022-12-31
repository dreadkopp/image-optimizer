<?php

namespace tests;

use Dreadkopp\ImageOptimizer\ImageHandler;
use finfo;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class HandlerTest extends TestCase
{

    public function testImageGetsQueried(): void
    {

        Storage::fake('image.png');
        Storage::fake('image.webp');

        Storage::disk('image.webp')->deleteDirectory('https');
        Storage::disk('image.png')->deleteDirectory('https');

        config(['filesystems.public.root' => __DIR__ . '/resources']);
        $original = 'https://www.gstatic.com/webp/gallery/1.jpg';
        $originalLocal = '/2.jpg';

        $handler = new ImageHandler();
        $png = $handler->getOptimized(base64_encode($original));
        $pngLocal = $handler->getOptimized(base64_encode($originalLocal));

        $_SERVER['HTTP_ACCEPT'] = ' image/webp';

        $webP = $handler->getOptimized(base64_encode($original));

        self::assertNotNull($png);
        self::assertNotNull($webP);

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeShouldBePng = $finfo->buffer($png);
        $mimeShouldBePngLocal = $finfo->buffer($pngLocal);
        $mimeShouldBeWebp = $finfo->buffer($webP);

        self::assertEquals('image/png', $mimeShouldBePng);
        self::assertEquals('image/png', $mimeShouldBePngLocal);
        self::assertEquals('image/webp', $mimeShouldBeWebp);

    }

}