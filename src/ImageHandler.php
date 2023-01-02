<?php
namespace Dreadkopp\ImageOptimizer;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image as ImageFacade;

class ImageHandler
{

    // maximum width of images in px
    const MAXWIDTH = 1300;
    const QUALITY = 80;

    public function getOptimized(string $base64EncodedPath, ?int $maxWidth = null, ?int $quality = null) :Response
    {
        $uploader = new Uploader();
        $fetcher = new ImageFetcher();

        $browserWantsWebp = $this->requestWantsWebp();

        $decodedPath = base64_decode($base64EncodedPath);


        try {
            $source = $fetcher->getOptimizedImageSource($decodedPath, $browserWantsWebp);

            return ImageFacade::cache(static function ($image) use ($source) {
                $image->make($source);
            },10, true)->response();
        } catch (OptimizedImageNotFound) {
            Log::info('Did not find optimized image for '.$decodedPath);
        }

        $source = $fetcher->getOriginalImageSource($decodedPath);

        $unOptimized =  ImageFacade::cache(static function ($image) use ($source) {
            $image->make($source);
        },10, true);

        if ($unOptimized->getWidth() > ($maxWidth ?? self::MAXWIDTH)) {
            $unOptimized->resize(($maxWidth ?? self::MAXWIDTH),null, function (Constraint $constraint) {
                $constraint->aspectRatio();
            });
        }

        $webPImage = $unOptimized->encode('webp',$quality ?? self::QUALITY);
        $plainImage = (clone $unOptimized)->encode('png',$quality ?? self::QUALITY);

        $uploader->upload($webPImage->getEncoded(), $decodedPath, true);
        $uploader->upload($plainImage->getEncoded(), $decodedPath, false);

        $image = $plainImage;
        if ($browserWantsWebp) {
            $image = $webPImage;
        }

        return $image->response();
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