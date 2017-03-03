<?php

namespace Brendt\Image;

use Amp\Parallel\Forking\Fork;
use AsyncInterop\Promise;
use Brendt\Image\Config\DefaultConfigurator;
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
     * @var bool
     */
    private $async;

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
     * @var Promise[]
     */
    protected $promises;

    /**
     * ResponsiveFactory constructor.
     *
     * @param ResponsiveFactoryConfigurator $configurator
     */
    public function __construct(ResponsiveFactoryConfigurator $configurator = null) {
        $configurator = $configurator ?? new DefaultConfigurator();
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
    public function create($src) {
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

        // TODO: This piece of code should be added as a size and not as a "default".
        // It's because the WidthScaler skips the default size.
        $width = $imageObject->getWidth();
        $responsiveImage->addSource($src, $width);

        $sizes = $this->scaler->scale($sourceImage, $imageObject);
        $this->createScaledImages($imageObject, $responsiveImage, $sizes);

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
     *
     * @TODO: refactor code duplication
     */
    public function createScaledImages(Image $imageObject, ResponsiveImage $responsiveImage, array $sizes) : ResponsiveImage {
        $urlPath = $responsiveImage->getUrlPath();
        $async = $this->async && Fork::supported();

        if ($async) {
            $factory = $this;
            $optimize = $this->optimize;

            $fork = Fork::spawn(function () use ($factory, $imageObject, $responsiveImage, $sizes, $urlPath, $optimize) {
                foreach ($sizes as $width => $height) {
                    $scaledFileSrc = trim("{$urlPath}/{$imageObject->filename}-{$width}.{$imageObject->extension}", '/');
                    $scaledFilePath = "{$factory->getPublicPath()}/{$scaledFileSrc}";

                    $scaledImage = $imageObject->resize((int) $width, (int) $height)->encode($imageObject->extension);
                    $factory->saveImageFile($scaledFilePath, $scaledImage);
                }

                if ($optimize) {
                    $factory->optimizeResponsiveImage($responsiveImage);
                }
            });

            $responsiveImage->setPromise($fork->join());
        }

        foreach ($sizes as $width => $height) {
            $scaledFileSrc = trim("{$urlPath}/{$imageObject->filename}-{$width}.{$imageObject->extension}", '/');
            $scaledFilePath = "{$this->publicPath}/{$scaledFileSrc}";

            $responsiveImage->addSource($scaledFileSrc, $width);

            if (!$async) {
                $deferred = new \Amp\Deferred();
                $responsiveImage->setPromise($deferred->promise());
                $deferred->resolve();

                $this->scaleImage($scaledFilePath, $imageObject, $width, $height);

                if ($this->optimize) {
                    $this->optimizeResponsiveImage($responsiveImage);
                }
            }
        }

        return $responsiveImage;
    }

    /**
     * Scale an image and save it.
     *
     * @param string $path
     * @param Image  $imageObject
     * @param        $width
     * @param        $height
     *
     * @return Image
     */
    public function scaleImage(string $path, Image $imageObject, $width, $height) : Image {
        $scaledImage = $imageObject->resize((int) $width, (int) $height)->encode($imageObject->extension);

        $this->saveImageFile($path, $scaledImage);

        return $scaledImage;
    }

    /**
     * Save the image file contents to a path
     *
     * @param string $path
     * @param string $image
     */
    public function saveImageFile(string $path, string $image) {
        if (!$this->enableCache || !$this->fs->exists($path)) {
            $this->fs->dumpFile($path, $image);
        }
    }

    /**
     * Optimize all sources of a Responsive Image
     *
     * @param ResponsiveImage $responsiveImage
     *
     * @return ResponsiveImage
     */
    public function optimizeResponsiveImage(ResponsiveImage $responsiveImage) : ResponsiveImage {
        foreach ($responsiveImage->getSrcset() as $imageFile) {
            $this->optimizer->optimize("{$this->publicPath}/{$imageFile}");
        }

        return $responsiveImage;
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

    /**
     * @param bool $async
     *
     * @return ResponsiveFactory
     */
    public function setAsync(bool $async) : ResponsiveFactory {
        $this->async = $async;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublicPath() : string {
        return $this->publicPath;
    }

}
