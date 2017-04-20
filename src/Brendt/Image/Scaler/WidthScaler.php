<?php

namespace Brendt\Image\Scaler;

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

        if ($this->includeSource && (!$this->maxWidth || $width <= $this->maxWidth)) {
            $sizes[$width] = $height;
        }

        while ($width >= $this->minWidth) {
            $width = floor($width * $this->stepModifier);
            $height = floor($height * $this->stepModifier);

            if (!$this->maxWidth || $width <= $this->maxWidth) {
                $sizes[(int) $width] = $height;
            }
        }

        return $sizes;
    }
}
