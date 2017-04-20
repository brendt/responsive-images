<?php

namespace Brendt\Image\Scaler;

use Brendt\Image\Config\DefaultConfigurator;
use Brendt\Image\ResponsiveImage;
use Intervention\Image\Image;
use Symfony\Component\Finder\SplFileInfo;

interface Scaler
{

    /**
     * Scaler constructor.
     *
     * @param DefaultConfigurator $configurator
     */
    public function __construct(DefaultConfigurator $configurator);

    /**
     * @param SplFileInfo $sourceFile
     * @param Image       $imageObject
     *
     * @return array
     */
    public function scale(SplFileInfo $sourceFile, Image $imageObject) : array;

    /**
     * @param $minFileSize
     *
     * @return mixed
     */
    public function setMinFileSize($minFileSize);

    /**
     * @param $minWidth
     *
     * @return mixed
     */
    public function setMinWidth($minWidth);

    /**
     * @param $maxFileSize
     *
     * @return mixed
     */
    public function setMaxFileSize($maxFileSize);

    /**
     * @param $maxWidth
     *
     * @return mixed
     */
    public function setMaxWidth($maxWidth);

    /**
     * @param $stepModifier
     *
     * @return mixed
     */
    public function setStepModifier($stepModifier);

    /**
     * @param $includeSource
     *
     * @return mixed
     */
    public function setIncludeSource(bool $includeSource) : Scaler;


}
