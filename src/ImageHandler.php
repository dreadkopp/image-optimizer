<?php
namespace Dreadkopp\ImageOptimizer;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image as ImageFacade;

class ImageHandler
{

    use DispatchesJobs;

    // maximum width of images in px
    const MAXWIDTH = 1300;
    const QUALITY = 80;
    public function __construct(protected ImageFetcher $fetcher)
    {
    }

    public function getOptimized(string $base64EncodedPath, ?int $maxWidth = null, ?int $quality = null) :Response
    {

        $browserWantsWebp = $this->requestWantsWebp();

        $decodedPath = base64_decode($base64EncodedPath);


        try {
            $source = $this->fetcher->getOptimizedImageSource($decodedPath, $browserWantsWebp);

            return ImageFacade::cache(static function ($image) use ($source) {
                $image->make($source);
            },10, true)->response();
        } catch (OptimizedImageNotFound) {
            Log::info('Did not find optimized image for '.$decodedPath);

            $this->dispatch(new OptimizeImageJob($decodedPath,($maxWidth ?? self::MAXWIDTH) ,($quality ?? self::QUALITY)));

            $source = $this->fetcher->getOriginalImageSource($decodedPath);

            return ImageFacade::cache(static function ($image) use ($source) {
                $image->make($source);
            },2, true)->response();
        }

    }

    protected function requestWantsWebp():bool
    {
        if (!array_key_exists('HTTP_ACCEPT', $_SERVER)) {
            return false;
        }
        if(str_contains($_SERVER['HTTP_ACCEPT'], 'image/webp')) {
            return true;
        }
        return false;
    }

}