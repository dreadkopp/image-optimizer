<?php

namespace Dreadkopp\ImageOptimizer;

use GuzzleHttp\Client;
use Intervention\Image\Image;
use Intervention\Image\Facades\Image as ImageFacade;

class ImageFetcher
{

    public function fetchImage(string $path): Image
    {



        // decide if local or remote
        if (str_starts_with($path, 'http')) {

            // Todo: strip query parameters

            $urlInfo = parse_url($path);
            $urlInfo['query'] = '';
            $urlInfo['fragment'] = '';
            $scheme = $urlInfo['scheme'];
            $urlInfo['scheme'] = '';
            $path = $scheme.'://'.implode('', $urlInfo);

            $client = new Client();
            $source = $client->get($path)->getBody()->getContents();
        } else {
            $source = file_get_contents(config('filesystems.public.root') . '/' . $path);
        }

        return ImageFacade::make($source);
    }

}