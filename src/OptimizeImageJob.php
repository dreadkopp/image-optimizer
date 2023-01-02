<?php

namespace Dreadkopp\ImageOptimizer;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Jobs\Job;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image as ImageFacade;

class OptimizeImageJob implements ShouldQueue
{




    public function __construct(
        protected string $pathToOriginalSource,
        protected int $maxWidth,
        protected int $quality
    )
    {
    }

    public function handle() :void
    {

        $fetcher = new ImageFetcher();
        $uploader = new Uploader();

        $source = $fetcher->getOriginalImageSource($this->pathToOriginalSource);

        $unOptimized =  ImageFacade::cache(static function ($image) use ($source) {
            $image->make($source);
        },10, true);

        if ($unOptimized->getWidth() > $this->maxWidth ) {
            $unOptimized->resize($this->maxWidth ,null, function (Constraint $constraint) {
                $constraint->aspectRatio();
            });
        }

        $webPImage = $unOptimized->encode('webp',$this->quality);
        $plainImage = (clone $unOptimized)->encode('png',$this->quality);

        $uploader->upload($webPImage->getEncoded(), $this->pathToOriginalSource, true);
        $uploader->upload($plainImage->getEncoded(), $this->pathToOriginalSource, false);

    }

}