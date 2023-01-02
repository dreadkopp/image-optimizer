<?php
namespace Dreadkopp\ImageOptimizer;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Intervention\Image\Facades\Image as ImageFacade;

class ImageServer
{
    public function getOptimizedImage(string $path, bool $webp) :\Intervention\Image\Image
    {

        // place locally or somewhere 'near' and cached as well
        $store = 'image.png';
        if ($webp) {
            $store = 'image.webp';
        }

        $source = Storage::disk($store)->get($path);

        if (!$source) {
            throw new OptimizedImageNotFound();
        }

        return ImageFacade::cache(static function ($image) use ($source) {
            $image->make($source);
        },10, true);

    }

}