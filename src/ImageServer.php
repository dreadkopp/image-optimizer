<?php
namespace Dreadkopp\ImageOptimizer;

use Illuminate\Support\Facades\Storage;

class ImageServer
{
    public function getOptimizedImage(string $path, bool $webp) :string
    {

        // place locally or somewhere 'near' and cached as well
        $store = 'image.png';
        if ($webp) {
            $store = 'image.webp';
        }

        $image = Storage::disk($store)->get($path);

        if (!$image) {
            throw new OptimizedImageNotFound();
        }

        return $image;

    }

}