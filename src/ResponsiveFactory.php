<?php

namespace Brendt\Image;

use Brendt\Image\Config\ResponsiveFactoryConfigurator;
use Brendt\Image\Exception\FileNotFoundException;
use Brendt\Image\Scaler\Scaler;
use Intervention\Image\ImageManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ResponsiveFactory
{

    /**
     * The image driver to use.
     * Available drivers: 'gd' and 'imagick'.
     *
     * @var string
     */
    protected $driver;

    /**
     * The source path to load images from.
     *
     * @var string
     */
    protected $sourcePath;

    /**
     * The public path to save rendered images.
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Enabled cache will stop generated images from being overwritten.
     *
     * @var bool
     */
    private $enableCache;

    /**
     * The Intervention image engine.
     *
     * @var ImageManager
     */
    protected $engine;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var Scaler
     */
    protected $scaler;

    /**
     * ResponsiveFactory constructor.
     *
     * @param ResponsiveFactoryConfigurator $configurator
     */
    public function __construct(ResponsiveFactoryConfigurator $configurator) {
        $configurator->configure($this);
        $this->sourcePath = rtrim($this->sourcePath, '/');
        $this->publicPath = rtrim($this->publicPath, '/');
        $this->engine = new ImageManager([
            'driver' => $this->driver,
        ]);

        $this->fs = new Filesystem();
        if (!$this->fs->exists($this->publicPath)) {
            $this->fs->mkdir($this->publicPath);
        }
    }

    /**
     * @param string $src
     *
     * @return ResponsiveImage
     * @throws FileNotFoundException
     */
    public function create($src) {
        $responsiveImage = new ResponsiveImage($src);
        $src = $responsiveImage->src();
        $sourceImage = $this->getImageFile($this->sourcePath, $src);

        if (!$sourceImage) {
            throw new FileNotFoundException("{$this->sourcePath}{$src}");
        }

        $publicImagePath = "{$this->publicPath}/{$src}";

        if (!$this->enableCache || !$this->fs->exists($publicImagePath)) {
            if ($this->fs->exists($publicImagePath)) {
                $this->fs->remove($publicImagePath);
            }

            $this->fs->dumpFile($publicImagePath, $sourceImage->getContents());
        }

        $extension = $sourceImage->getExtension();
        $fileName = str_replace(".{$extension}", '', $sourceImage->getFilename());

        $urlParts = explode('/', $src);
        array_pop($urlParts);
        $urlPath = implode('/', $urlParts);

        $responsiveImage->setExtension($extension);
        $responsiveImage->setFileName($fileName);
        $responsiveImage->setUrlPath($urlPath);

        $imageObject = $this->engine->make($sourceImage->getPathname());
        $width = $imageObject->getWidth();
        $responsiveImage->addSource($src, $width);

        $responsiveImage = $this->scaler->scale($responsiveImage, $imageObject);

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

    /**
     * @param string $driver
     *
     * @return ResponsiveFactory
     */
    public function setDriver($driver) {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @param string $publicPath
     *
     * @return ResponsiveFactory
     */
    public function setPublicPath($publicPath) {
        $this->publicPath = $publicPath;

        return $this;
    }

    /**
     * @param boolean $enableCache
     *
     * @return ResponsiveFactory
     */
    public function setEnableCache($enableCache) {
        $this->enableCache = $enableCache;

        return $this;
    }

    /**
     * @param string $sourcePath
     *
     * @return ResponsiveFactory
     */
    public function setSourcePath($sourcePath) {
        $this->sourcePath = $sourcePath;

        return $this;
    }

    /**
     * @param Scaler $scaler
     */
    public function setScaler($scaler) {
        $this->scaler = $scaler;
    }

}
