<?php

namespace Brendt\Image\Scaler;

use Brendt\Image\ResponsiveImage;
use Intervention\Image\Image;
use Symfony\Component\Finder\SplFileInfo;

class WidthScaler extends AbstractScaler
{

    /**
     * @param SplFileInfo $sourceFile
     * @param Image       $imageObject
     *
     * @return array
     */
    public function scale(SplFileInfo $sourceFile, Image $imageObject) : array {
        $width = $imageObject->getWidth();
        $height = $imageObject->getHeight();

        $sizes = [];
        while ($width >= $this->minWidth) {
            $width = floor($width * $this->stepModifier);
            $height = floor($height * $this->stepModifier);

            $sizes[(int) $width] = $height;
        }

        return $sizes;
    }
}
