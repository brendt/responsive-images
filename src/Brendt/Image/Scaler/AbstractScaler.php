<?php

namespace Brendt\Image\Scaler;

use Brendt\Image\Config\DefaultConfigurator;

abstract class AbstractScaler implements Scaler
{
    /**
     * @var integer
     */
    protected $minFileSize;

    /**
     * @var integer
     */
    protected $minWidth;

    /**
     * @var integer
     */
    protected $maxFileSize;

    /**
     * @var integer
     */
    protected $maxWidth;

    /**
     * @var float
     */
    protected $stepModifier;

    /**
     * @var bool
     */
    protected $includeSource;

    /**
     * Scaler constructor.
     *
     * @param DefaultConfigurator $configurator
     */
    public function __construct(DefaultConfigurator $configurator) {
        $configurator->configureScaler($this);
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
     * @param mixed $maxFileSize
     *
     * @return AbstractScaler
     */
    public function setMaxFileSize($maxFileSize) {
        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    /**
     * @param mixed $maxWidth
     *
     * @return AbstractScaler
     */
    public function setMaxWidth($maxWidth) {
        $this->maxWidth = $maxWidth;

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

    /**
     * @param bool $includeSource
     *
     * @return Scaler
     */
    public function setIncludeSource(bool $includeSource) : Scaler {
        $this->includeSource = $includeSource;

        return $this;
    }

}
