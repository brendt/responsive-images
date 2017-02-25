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
     * @param $sourcePath
     *
     * @return mixed
     */
    public function setSourcePath($sourcePath);

    /**
     * @param $publicPath
     *
     * @return mixed
     */
    public function setPublicPath($publicPath);

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
     * @param $stepModifier
     *
     * @return mixed
     */
    public function setStepModifier($stepModifier);

    /**
     * @param $enableCache
     *
     * @return mixed
     */
    public function setEnableCache($enableCache);


}
