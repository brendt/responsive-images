<?php

namespace Brendt\Image\Scaler;

use Brendt\Image\ResponsiveImage;
use Intervention\Image\Image;

class WidthScaler extends AbstractScaler
{

    /**
     * @param ResponsiveImage $responsiveImage
     * @param Image           $imageObject
     *
     * @return ResponsiveImage
     */
    public function scale(ResponsiveImage $responsiveImage, Image $imageObject) {
        $width = $imageObject->getWidth();
        $height = $imageObject->getHeight();
        $fileName = $responsiveImage->getFileName();
        $extension = $responsiveImage->getExtension();
        $urlPath = $responsiveImage->getUrlPath();

        while ($width >= $this->minWidth) {
            $scaledName = "{$fileName}-{$width}.{$extension}";
            $scaledSrc = "{$urlPath}/{$scaledName}";
            $responsiveImage->addSource($scaledSrc, $width);

            $publicScaledPath = "{$this->publicPath}/{$urlPath}/{$scaledName}";
            if (!$this->enableCache || !$this->fs->exists($publicScaledPath)) {
                $this->fs->dumpFile(
                    $publicScaledPath,
                    $imageObject->resize($width, $height)->encode($extension)
                );
            }

            $width = floor($width * $this->stepModifier);
            $height = floor($height * $this->stepModifier);
        }

        return $responsiveImage;
    }
}
