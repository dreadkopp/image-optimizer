<?php

namespace Dreadkopp\ImageOptimizer;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class ImageFetcher
{

    public function fetchImage(string $path) : ImageInterface
    {
        // strip query parameters

        // decide if local or remote

        if (str_starts_with($path,'http')) {
            $client = new Client();
            $source = $client->get($path)->getBody()->getContents();
        } else {
            $source = file_get_contents(config('filesystems.public.root').'/'.$path);
        }

        return (new ImageManager())->make($source);
    }

}