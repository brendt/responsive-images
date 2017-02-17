<?php

namespace Brendt\Image\Scaler;

use Brendt\Image\Config\DefaultConfigurator;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractScaler implements Scaler
{

    /**
     * @var string
     */
    protected $sourcePath = '';

    /**
     * @var string
     */
    protected $publicPath = '';

    /**
     * @var boolean
     */
    protected $enableCache;

    /**
     * @var integer
     */
    protected $minFileSize;

    /**
     * @var integer
     */
    protected $minWidth;

    /**
     * @var float
     */
    protected $stepModifier;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * Scaler constructor.
     *
     * @param DefaultConfigurator $configurator
     */
    public function __construct(DefaultConfigurator $configurator) {
        $configurator->configureScaler($this);

        $this->fs = new Filesystem();
    }

    /**
     * @param mixed $sourcePath
     *
     * @return AbstractScaler
     */
    public function setSourcePath($sourcePath) {
        $this->sourcePath = $sourcePath;

        return $this;
    }

    /**
     * @param mixed $publicPath
     *
     * @return AbstractScaler
     */
    public function setPublicPath($publicPath) {
        $this->publicPath = $publicPath;

        return $this;
    }

    /**
     * @param mixed $enableCache
     *
     * @return AbstractScaler
     */
    public function setEnableCache($enableCache) {
        $this->enableCache = $enableCache;

        return $this;
    }

    /**
     * @param mixed $minFileSize
     *
     * @return AbstractScaler
     */
    public function setMinFileSize($minFileSize) {
        $this->minFileSize = $minFileSize;

        return $this;
    }

    /**
     * @param mixed $minWidth
     *
     * @return AbstractScaler
     */
    public function setMinWidth($minWidth) {
        $this->minWidth = $minWidth;

        return $this;
    }

    /**
     * @param mixed $stepModifier
     *
     * @return AbstractScaler
     */
    public function setStepModifier($stepModifier) {
        $this->stepModifier = $stepModifier;

        return $this;
    }

}
