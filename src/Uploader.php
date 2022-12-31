<?php
namespace Dreadkopp\ImageOptimizer;

use Illuminate\Support\Facades\Storage;

class Uploader
{
    public function upload(string $image, string $path, bool $webp) :void
    {
        $store = 'image.png';
        if ($webp) {
            $store = 'image.webp';
        }
        Storage::disk($store)->put($path, $image);
    }

}