<?php

namespace Brendt\Image;

use Brendt\Image\Config\ResponsiveFactoryConfigurator;
use Brendt\Image\Exception\FileNotFoundException;
use Brendt\Image\Scaler\Scaler;
use ImageOptimizer\Optimizer;
use ImageOptimizer\OptimizerFactory;
use Intervention\Image\Image;
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
     * Enable optimizers will run several image optimizers on the saved files.
     *
     * @var bool
     */
    private $optimize;

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
     * @var Optimizer
     */
    protected $optimizer;

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
        $this->optimizer = (new OptimizerFactory())->get();
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
    public function create($src) : ResponsiveImage {
        $responsiveImage = new ResponsiveImage($src);
        $src = $responsiveImage->src();
        $sourceImage = $this->getImageFile($this->sourcePath, $src);

        if (!$sourceImage) {
            throw new FileNotFoundException("{$this->sourcePath}{$src}");
        }

        $extension = $sourceImage->getExtension();
        $fileName = str_replace(".{$extension}", '', $sourceImage->getFilename());
        $publicImagePath = "{$this->publicPath}/{$src}";

        $urlParts = explode('/', $src);
        array_pop($urlParts);
        $urlPath = implode('/', $urlParts);

        $responsiveImage->setExtension($extension);
        $responsiveImage->setFileName($fileName);
        $responsiveImage->setUrlPath($urlPath);

        if ($this->enableCache && $this->fs->exists($publicImagePath)) {
            /** @var SplFileInfo[] $cachedFiles */
            $cachedFiles = Finder::create()->files()->in("{$this->publicPath}/{$sourceImage->getRelativePath()}")->name("{$fileName}-*.{$extension}");

            foreach ($cachedFiles as $cachedFile) {
                $cachedFilename = $cachedFile->getFilename();
                $size = (int) str_replace(".{$extension}", '', str_replace("{$fileName}-", '', $cachedFilename));

                $responsiveImage->addSource("{$urlPath}/{$cachedFilename}", $size);
            }

            return $responsiveImage;
        }

        if (!$this->enableCache || !$this->fs->exists($publicImagePath)) {
            $this->fs->dumpFile($publicImagePath, $sourceImage->getContents());
        }

        $imageObject = $this->engine->make($sourceImage->getPathname());
        $width = $imageObject->getWidth();
        $responsiveImage->addSource($src, $width);

        $sizes = $this->scaler->scale($sourceImage, $imageObject);
        $this->createScaledImages($sizes, $imageObject, $responsiveImage);

        if ($this->optimize) {
            $this->optimizeResponsiveImage($responsiveImage);
        }

        return $responsiveImage;
    }

    /**
     * Create scaled image files and add them as sources to a Responsive Image, based on an array of file sizes:
     * [
     *      width => height,
     *      ...
     * ]
     *
     * @param array           $sizes
     * @param Image           $imageObject
     * @param ResponsiveImage $responsiveImage
     *
     * @return ResponsiveImage
     */
    private function createScaledImages(array $sizes, Image $imageObject, ResponsiveImage $responsiveImage) : ResponsiveImage {
        $urlPath = $responsiveImage->getUrlPath();

        foreach ($sizes as $width => $height) {
            $scaledFileSrc = "{$urlPath}/{$imageObject->filename}-{$width}.{$imageObject->extension}";
            $scaledFilePath = "{$this->publicPath}/{$scaledFileSrc}";
            $scaledImage = $imageObject->resize((int) $width, (int) $height)->encode($imageObject->extension);

            $this->saveImageFile($scaledFilePath, $scaledImage);
            $responsiveImage->addSource($scaledFileSrc, $width);
        }

        return $responsiveImage;
    }

    /**
     * Optimize all sources of a Responsive Image
     *
     * @param ResponsiveImage $responsiveImage
     *
     * @return ResponsiveImage
     */
    private function optimizeResponsiveImage(ResponsiveImage $responsiveImage) : ResponsiveImage {
        foreach ($responsiveImage->getSrcset() as $imageFile) {
            $this->optimizer->optimize("{$this->publicPath}/{$imageFile}");
        }

        return $responsiveImage;
    }

    /**
     * Save the image file contents to a path
     *
     * @param string $path
     * @param string $image
     */
    private function saveImageFile(string $path, string $image) {
        if (!$this->enableCache || !$this->fs->exists($path)) {
            $this->fs->dumpFile($path, $image);
        }
    }

    /**
     * @param string $directory
     * @param string $path
     *
     * @return SplFileInfo
     */
    private function getImageFile(string $directory, string $path) : SplFileInfo {
        $iterator = Finder::create()->files()->in($directory)->path(ltrim($path, '/'))->getIterator();
        $iterator->rewind();

        return $iterator->current();
    }

    /**
     * @param string $driver
     *
     * @return ResponsiveFactory
     */
    public function setDriver($driver) : ResponsiveFactory {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @param string $publicPath
     *
     * @return ResponsiveFactory
     */
    public function setPublicPath($publicPath) : ResponsiveFactory {
        $this->publicPath = $publicPath;

        return $this;
    }

    /**
     * @param boolean $enableCache
     *
     * @return ResponsiveFactory
     */
    public function setEnableCache($enableCache) : ResponsiveFactory {
        $this->enableCache = $enableCache;

        return $this;
    }

    /**
     * @param string $sourcePath
     *
     * @return ResponsiveFactory
     */
    public function setSourcePath($sourcePath) : ResponsiveFactory {
        $this->sourcePath = $sourcePath;

        return $this;
    }

    /**
     * @param Scaler $scaler
     *
     * @return ResponsiveFactory
     */
    public function setScaler($scaler) : ResponsiveFactory {
        $this->scaler = $scaler;

        return $this;
    }

    /**
     * @param bool $optimize
     *
     * @return ResponsiveFactory
     */
    public function setOptimize(bool $optimize) : ResponsiveFactory {
        $this->optimize = $optimize;

        return $this;
    }

}
