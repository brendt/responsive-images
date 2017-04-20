<?php

namespace Brendt\Image\Scaler;

use Intervention\Image\Image;
use Symfony\Component\Finder\SplFileInfo;

class SizesScaler extends AbstractScaler
{

    /**
     * @var array
     */
    private $sizes = [];

    /**
     * @param array $sizes
     *
     * @return SizesScaler
     */
    public function setSizes(array $sizes) : SizesScaler {
        $this->sizes = $sizes;

        return $this;
    }

    /**
     * @param SplFileInfo $sourceFile
     * @param Image       $imageObject
     *
     * @return array
     */
    public function scale(SplFileInfo $sourceFile, Image $imageObject) : array {
        $imageWidth = $imageObject->getWidth();
        $imageHeight = $imageObject->getHeight();
        $ratio = $imageHeight / $imageWidth;

        $sizes = [];

        if ($this->includeSource) {
            $sizes[$imageWidth] = $imageHeight;
        }

        foreach ($this->sizes as $width) {
            if ($width > $imageWidth) {
                continue;
            }

            $sizes[$width] = round($width * $ratio);
        }

        return $sizes;
    }
}
