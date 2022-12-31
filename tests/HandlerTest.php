<?php

namespace tests;

use Dreadkopp\ImageOptimizer\ImageHandler;
use finfo;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Orchestra\Testbench\TestCase;

class HandlerTest extends TestCase
{

    public function testImageGetsQueried(): void
    {

        Storage::fake('image.png');
        Storage::fake('image.webp');

        Storage::disk('image.webp')->deleteDirectory('https');
        Storage::disk('image.png')->deleteDirectory('https');

        $this->app->bind('image', fn() => new ImageManager());

        config(['filesystems.public.root' => __DIR__ . '/resources']);
        $original = 'https://www.gstatic.com/webp/gallery/1.jpg?foo=bar#anchor';
        $originalLocal = '/2.jpg';

        $handler = new ImageHandler();
        $png = $handler->getOptimized(base64_encode($original))->getContent();
        $pngLocal = $handler->getOptimized(base64_encode($originalLocal))->getContent();

        $_SERVER['HTTP_ACCEPT'] = ' image/webp';

        $webP = $handler->getOptimized(base64_encode($original))->getContent();

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