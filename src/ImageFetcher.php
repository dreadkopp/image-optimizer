<?php

namespace Dreadkopp\ImageOptimizer;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Image;
use Intervention\Image\Facades\Image as ImageFacade;

class ImageFetcher
{

    public function getOriginalImageSource(string $path): string
    {
        // decide if local or remote
        if (!str_starts_with($path, 'http')) {
            return config('filesystems.public.root') . '/' . $path;
        }

        //strip query parameters

        $urlInfo = parse_url($path);
        $urlInfo['query'] = '';
        $urlInfo['fragment'] = '';
        $scheme = $urlInfo['scheme'];
        $urlInfo['scheme'] = '';
        $path = $scheme . '://' . implode('', $urlInfo);

        $client = new Client();
        return $client->get($path)->getBody()->getContents();


    }

    public function getOptimizedImageSource(string $path, bool $webp) :string
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

        return $source;

    }


}