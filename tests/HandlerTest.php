<?php

namespace Dreadkopp\ImageOptimizer\Tests;

use Dreadkopp\ImageOptimizer\ImageFetcher;
use Dreadkopp\ImageOptimizer\ImageHandler;
use Dreadkopp\ImageOptimizer\OptimizeImageJob;
use finfo;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageManager;
use Orchestra\Testbench\TestCase;

/**
 * @covers \Dreadkopp\ImageOptimizer\ImageHandler
 * @covers \Dreadkopp\ImageOptimizer\ImageFetcher
 * @covers \Dreadkopp\ImageOptimizer\Uploader
 */
class HandlerTest extends TestCase
{


    public function queryDataProvider(): array
    {
        return [
            'remote png' => [
                'source' => 'https://www.gstatic.com/webp/gallery/1.jpg?foo=bar#anchor',
                'accept' => 'image/nope',
                'expectedMime' => 'image/png'
            ],
            'remote webp' => [
                'source' => 'https://www.gstatic.com/webp/gallery/1.jpg?foo=bar#anchor',
                'accept' => 'image/webp',
                'expectedMime' => 'image/webp'
            ],
            'local png' => [
                'source' => '/2.jpg',
                'accept' => 'image/nope',
                'expectedMime' => 'image/png'
            ],
            'local webp' => [
                'source' => '/2.jpg',
                'accept' => 'image/webp',
                'expectedMime' => 'image/webp'
            ],

        ];
    }

    /** @dataProvider queryDataProvider */
    public function testImageGetsQueried(string $source, string $accepts, string $expectedMime): void
    {
        $handler = new ImageHandler(new ImageFetcher());
        $_SERVER['HTTP_ACCEPT'] = $accepts;

        $png = $handler->getOptimized(base64_encode($source))->getContent();

        self::assertNotNull($png);

        $finfo = new finfo(FILEINFO_MIME_TYPE);


        // first hit, nothing changed
        self::assertEquals('image/jpeg', $finfo->buffer($png));

        Queue::assertPushed(OptimizeImageJob::class, function (OptimizeImageJob $job) {
            $job->handle();
            return true;
        });

        $png = $handler->getOptimized(base64_encode($source))->getContent();
        self::assertEquals($expectedMime, $finfo->buffer($png));

    }

    public function testResizeParameterIsHonored(): void
    {
        $originalLocal = '/2.jpg';

        $handler = new ImageHandler(new ImageFetcher());
        // hit twice
        $jpg = $handler->getOptimized(base64_encode($originalLocal), 100)->getContent();
        self::assertEquals(550, Image::make($jpg)->getWidth());

        Queue::assertPushed(OptimizeImageJob::class, function (OptimizeImageJob $job) {
            $job->handle();
            return true;
        });


        $png = $handler->getOptimized(base64_encode($originalLocal), 100)->getContent();
        self::assertEquals(100, Image::make($png)->getWidth());
    }

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Storage::fake('image.png');
        Storage::fake('image.webp');

        Storage::disk('image.webp')->deleteDirectory('https');
        Storage::disk('image.png')->deleteDirectory('https');

        $this->app->bind('image', fn() => new ImageManager());

        config(['filesystems.public.root' => __DIR__ . '/resources']);
    }

}