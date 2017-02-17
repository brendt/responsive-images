<?php

namespace Brendt\Image\Scaler;

use Brendt\Image\ResponsiveImage;
use Intervention\Image\Image;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FileSizeScaler extends AbstractScaler
{

    /**
     * @param ResponsiveImage $responsiveImage
     * @param Image           $imageObject
     *
     * @return ResponsiveImage
     */
    public function scale(ResponsiveImage $responsiveImage, Image $imageObject) {
        $fileName = $responsiveImage->getFileName();
        $extension = $responsiveImage->getExtension();
        $urlPath = $responsiveImage->getUrlPath();

        $sourceFile = $this->getImageFile($this->sourcePath, $responsiveImage->src());

        $fileSize = $sourceFile->getSize();
        $width = $imageObject->getWidth();
        $height = $imageObject->getHeight();
        $ratio = $height / $width;
        $area = $width * $width * $ratio;
        $pixelPrice = $fileSize / $area;

        // Magic formula.
        $newWidth = floor(sqrt(($fileSize / $pixelPrice) / $ratio));
        while ($fileSize > $this->minFileSize && $newWidth > $this->minWidth) {
            $scaledName = "{$fileName}-{$newWidth}.{$extension}";
            $scaledSrc = "{$urlPath}/{$scaledName}";
            $responsiveImage->addSource($scaledSrc, $newWidth);

            $publicScaledPath = "{$this->publicPath}/{$urlPath}/{$scaledName}";
            if (!$this->enableCache || !$this->fs->exists($publicScaledPath)) {
                $this->fs->dumpFile(
                    $publicScaledPath,
                    $imageObject->resize($newWidth, $newWidth * $ratio)->encode($extension)
                );
            }

            $fileSize = $fileSize * $this->stepModifier;
            $newWidth = floor(sqrt(($fileSize / $pixelPrice) / $ratio));
        }

        return $responsiveImage;
    }

    /**
     * @param string $directory
     * @param string $path
     *
     * @return SplFileInfo
     */
    private function getImageFile($directory, $path) {
        $iterator = Finder::create()->files()->in($directory)->path(ltrim($path, '/'))->getIterator();
        $iterator->rewind();

        return $iterator->current();
    }
}
