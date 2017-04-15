<?php

namespace Brendt\Image\Scaler;

use Intervention\Image\Image;
use Symfony\Component\Finder\SplFileInfo;

class FileSizeScaler extends AbstractScaler
{

    /**
     * @param SplFileInfo $sourceFile
     * @param Image       $imageObject
     *
     * @return array
     */
    public function scale(SplFileInfo $sourceFile, Image $imageObject) : array {
        $fileSize = $sourceFile->getSize();
        $width = $imageObject->getWidth();
        $height = $imageObject->getHeight();
        $ratio = $height / $width;
        $area = $width * $width * $ratio;
        $pixelPrice = $fileSize / $area;

        $sizes = [];

        do {
            // Magic formula.
            $newWidth = floor(sqrt(($fileSize / $pixelPrice) / $ratio));

            if ((!$this->maxFileSize || $fileSize <= $this->maxFileSize) && (!$this->maxWidth || $newWidth <= $this->maxWidth)) {
                $sizes[(int) $newWidth] = (int) $newWidth * $ratio;
            }

            $fileSize = $fileSize * $this->stepModifier;
        } while ($fileSize > $this->minFileSize && $newWidth > $this->minWidth);

        return $sizes;
    }
}
