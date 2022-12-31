<?php
namespace Dreadkopp\ImageOptimizer;

use Illuminate\Http\Response;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;

class ImageHandler
{

    // maximum width of images in px
    const MAXWIDTH = 1300;

    public function getOptimized(string $base64EncodedPath) :Response
    {
        $server = new ImageServer();
        $uploader = new Uploader();
        $fetcher = new ImageFetcher();

        $browserWantsWebp = $this->requestWantsWebp();

        $decodedPath = base64_decode($base64EncodedPath);


        try {
            $image = $server->getOptimizedImage($decodedPath, $browserWantsWebp);
        } catch (OptimizedImageNotFound) {
            $unOptimized = $fetcher->fetchImage($decodedPath);

            if ($unOptimized->getWidth() > self::MAXWIDTH) {
                $unOptimized->resize(self::MAXWIDTH,null, function (Constraint $constraint) {
                    $constraint->aspectRatio();
                });
            }
            $webPImage = $unOptimized->stream('webp',80)->getContents();
            $plainImage = $unOptimized->stream('png',80)->getContents();

            $uploader->upload($webPImage, $decodedPath, true);
            $uploader->upload($plainImage, $decodedPath, false);

            $image = $plainImage;
            if ($browserWantsWebp) {
                $image = $webPImage;
            }
        }

        return Image::make($image)->response();
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