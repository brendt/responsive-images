<?php

namespace Brendt\Image;

class ResponsiveImage
{

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
     * @var string
     */
    private $fileName = '';

    /**
     * @var string
     */
    private $extension = '';

    /**
     * @var string
     */
    private $urlPath = '';

    /**
     * ResponsiveImage constructor.
     *
     * @param      $src
     */
    public function __construct($src) {
        $src = preg_replace('/^(\/|\.\/)/', '', $src);

        $this->src = "/{$src}";
    }

    /**
     * @return string
     */
    public function src() {
        return $this->src;
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

        foreach ($sources as $url => $width) {
            $url = ltrim($url, '/');
            $width = str_replace('px', '', $width);

            $this->srcset[$width] = "/{$url}";
        }

        krsort($this->srcset);

        return $this;
    }

    /**
     * @return string
     */
    public function srcset() {
        $srcset = [];

        foreach ($this->srcset as $w => $url) {
            $srcset[] = "{$url} {$w}w";
        }

        return implode(',', $srcset);
    }

    /**
     * @param array|string $sizes
     * @param null         $value
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
    public function getFileName() {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName) {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getExtension() {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    public function setExtension($extension) {
        $this->extension = $extension;
    }

    /**
     * @return string
     */
    public function getUrlPath() {
        return $this->urlPath;
    }

    /**
     * @param string $urlPath
     */
    public function setUrlPath($urlPath) {
        $this->urlPath = $urlPath;
    }

    /**
     * @return \string[]
     */
    public function getSrcset() {
        return $this->srcset;
    }

    /**
     * @return string
     */
    public function getSrc() {
        return $this->src;
    }

}
