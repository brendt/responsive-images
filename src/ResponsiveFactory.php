<?php

namespace brendt\image;

use brendt\image\config\ResponsiveFactoryConfigurator;
use brendt\image\exception\FileNotFoundException;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ResponsiveFactory {

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
     * The minimum file size of generated images.
     * No image with a size less then this amount (in KB), will be generated.
     *
     * @var integer
     */
    protected $minSize;

    /**
     * Enabled cache will stop generated images from being overwritten.
     *
     * @var bool
     */
    private $enableCache;

    /**
     * A percentage (between 0 and 1) to decrease image sizes with.
     *
     * @var float
     */
    protected $stepModifier;

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
        $image = new ResponsiveImage($src);
        $src = $image->src();
        $sourceImage = $this->getImageFile($this->sourcePath, $src);
        $publicImagePath = "{$this->publicPath}/{$src}";

        if (!$this->enableCache || !$this->fs->exists($publicImagePath)) {
            if ($this->fs->exists($publicImagePath)) {
                $this->fs->remove($publicImagePath);
            }

            $this->fs->dumpFile($publicImagePath, $sourceImage->getContents());
        }

        $extension = $sourceImage->getExtension();
        $name = str_replace(".{$extension}", '', $sourceImage->getFilename());

        $urlParts = explode('/', $src);
        array_pop($urlParts);
        $urlPath = implode('/', $urlParts);

        $imageObject = $this->engine->make($sourceImage->getPathname());
        $width = $imageObject->getWidth();
        $height = $imageObject->getHeight();
        $stepWidth = (int) ($width * $this->stepModifier);
        $stepHeight = (int) ($height * $this->stepModifier);
        $width -= $stepWidth;
        $height -= $stepHeight;

        while ($width >= $this->minSize) {
            $scaledName = "{$name}-{$width}.{$extension}";
            $scaledSrc = "{$urlPath}/{$scaledName}";
            $image->addSource($scaledSrc, $width);

            $publicScaledPath = "{$this->publicPath}/{$urlPath}/{$scaledName}";
            if (!$this->enableCache || !$this->fs->exists($publicScaledPath)) {
                $this->fs->dumpFile(
                    $publicScaledPath,
                    $imageObject->resize($width, $height)->encode($extension)
                );
            }

            $width -= $stepWidth;
            $height -= $stepHeight;
        }

        return $image;
    }

    /**
     * @param        $directory
     * @param string $path
     *
     * @return SplFileInfo
     */
    private function getImageFile($directory, $path) {
        $iterator = Finder::create()->files()->in($directory)->path($path)->getIterator();
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
     * @param int $minSize
     *
     * @return ResponsiveFactory
     */
    public function setMinSize($minSize) {
        $this->minSize = $minSize;

        return $this;
    }

    /**
     * @param float $stepModifier
     *
     * @return ResponsiveFactory
     */
    public function setStepModifier($stepModifier) {
        $this->stepModifier = $stepModifier;

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

}
