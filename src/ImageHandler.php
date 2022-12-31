<?php
namespace Dreadkopp\ImageOptimizer;

class ImageHandler
{

    // maximum width of images in px
    const MAXWIDTH = 1300;

    public function getOptimized(string $base64EncodedPath) :string
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
                $unOptimized->resize(self::MAXWIDTH);
            }
            $webPImage = $unOptimized->toWebp();
            $plainImage = $unOptimized->toPng();

            $uploader->upload($webPImage, $decodedPath, true);
            $uploader->upload($plainImage, $decodedPath, false);

            $image = $plainImage;
            if ($browserWantsWebp) {
                $image = $webPImage;
            }
        }

        return $image;
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