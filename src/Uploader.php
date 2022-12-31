<?php
namespace Dreadkopp\ImageOptimizer;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\EncodedImage;

class Uploader
{
    public function upload(EncodedImage $image, string $path, bool $webp) :void
    {
        $store = 'image.png';
        if ($webp) {
            $store = 'image.webp';
        }
        $blob = $image->toString();
        Storage::disk($store)->put($path, $blob);
    }

}