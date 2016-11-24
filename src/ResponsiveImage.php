<?php

namespace brendt\image;

use brendt\image\exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ResponsiveImage {

    /**
     * @var string
     */
    private $src = '';

    /**
     * @var string[]
     */
    private $srcset = [];

    /**
     * @var string[]
     */
    private $sizes = [];

    /**
     * @var SplFileInfo
     */
    private $file;

    /**
     * ResponsiveImage constructor.
     *
     * @param      $src
     */
    public function __construct($src) {
        $this->file = $this->getImageFile($src);
        $this->src = "/{$this->file->getRelativePathname()}";
    }

    /**
     * @param      $sources
     * @param null $value
     *
     * @return ResponsiveImage
     */
    public function addSource($sources, $value = null) {
        if (!is_array($sources) && $value) {
            $sources = [$sources => $value];
        } elseif (!is_array($sources)) {
            return $this;
        }

        foreach ($sources as $path => $width) {
            $path = ltrim($path, './');
            $width = rtrim($width, 'px');

            $this->srcset[$width] = "/{$path}";
        }

        krsort($this->srcset);

        return $this;
    }

    /**
     * @param array|string  $sizes
     * @param null          $value
     *
     * @return ResponsiveImage
     */
    public function addSizes($sizes, $value = null) {
        if (!is_array($sizes) && $value) {
            $sizes = [$sizes => $value];
        } elseif (!is_array($sizes)) {
            return $this;
        }

        foreach ($sizes as $media => $value) {
            $this->sizes[$media] = $value;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function src() {
        return $this->src;
    }

    /**
     * @return string
     */
    public function srcset() {
        $srcset = [];

        foreach ($this->srcset as $w => $path) {
            $srcset[] = "{$path} {$w}w";
        }

        return implode(',', $srcset);
    }

    /**
     * @return string
     */
    public function sizes() {
        $sizes = [];

        foreach ($this->sizes as $media => $value) {
            $media = rtrim(ltrim($media, '('), ')');

            if (is_numeric($media)) {
                $sizes[] = "$value";
            } else {
                $sizes[] = "({$media}) $value";
            }
        }

        return implode(', ', $sizes);
    }

    /**
     * @return string
     */
    public function getSrc() {
        return $this->src;
    }

    /**
     * @return mixed|\Symfony\Component\Finder\SplFileInfo
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @param $src
     *
     * @return SplFileInfo
     * @throws FileNotFoundException
     */
    private function getImageFile($src) {
        $files = Finder::create()->files()->in('.')->path(trim($src, './'));

        foreach ($files as $file) {
            return $file;
        }

        throw new FileNotFoundException($src);
    }

}
