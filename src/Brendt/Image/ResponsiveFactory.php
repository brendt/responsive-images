<?php

namespace Brendt\Image;

use Brendt\Image\Config\DefaultConfigurator;
use Brendt\Image\Config\ResponsiveFactoryConfigurator;
use Brendt\Image\Exception\FileNotFoundException;
use Brendt\Image\Scaler\Scaler;
use ImageOptimizer\Optimizer;
use ImageOptimizer\OptimizerFactory;
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
    private $rebase;

    /**
     * @var array
     */
    private $optimizerOptions = [];

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
    public function __construct(ResponsiveFactoryConfigurator $configurator = null) {
        $configurator = $configurator ?? new DefaultConfigurator();
        $configurator->configure($this);

        $this->sourcePath = rtrim($this->sourcePath, '/');
        $this->publicPath = rtrim($this->publicPath, '/');

        $this->engine = new ImageManager([
            'driver' => $this->driver,
        ]);

        $this->optimizer = (new OptimizerFactory($this->optimizerOptions))->get();
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
        $sourceFilename = $this->rebase ? pathinfo($src, PATHINFO_BASENAME) : $src;
        $sourceFile = $this->getImageFile($this->sourcePath, $sourceFilename);

        $filename = pathinfo($sourceFile->getFilename(), PATHINFO_FILENAME);
        $urlPath = '/' . trim(pathinfo($src, PATHINFO_DIRNAME), '/');

        $responsiveImage->setExtension($sourceFile->getExtension());
        $responsiveImage->setFileName($filename);
        $responsiveImage->setUrlPath($urlPath);

        if ($cachedResponsiveImage = $this->getCachedResponsiveImage($responsiveImage, $sourceFile)) {
            return $cachedResponsiveImage;
        }

        $this->fs->dumpFile("{$this->publicPath}{$src}", $sourceFile->getContents());
        $this->createScaledImages($sourceFile, $responsiveImage);
        
        return $responsiveImage;
    }

    /**
     * @param ResponsiveImage $responsiveImage
     * @param SplFileInfo     $imageFile
     *
     * @return ResponsiveImage|null
     */
    public function getCachedResponsiveImage(ResponsiveImage $responsiveImage, SplFileInfo $imageFile) {
        $src = $responsiveImage->src();
        $publicImagePath = "{$this->publicPath}{$src}";

        if ($this->enableCache && $this->fs->exists($publicImagePath)) {
            $extension = $imageFile->getExtension();
            $publicDirectory = $this->rebase ? trim(pathinfo($src, PATHINFO_DIRNAME), '/') : $imageFile->getRelativePath();
            $imageFilename = pathinfo($imageFile->getFilename(), PATHINFO_FILENAME);
            
            /** @var SplFileInfo[] $cachedFiles */
            $cachedFiles = Finder::create()->files()->in("{$this->publicPath}/{$publicDirectory}")->name("{$imageFilename}-*.{$extension}");

            foreach ($cachedFiles as $cachedFile) {
                $cachedFilename = $cachedFile->getFilename();
                $size = (int) str_replace(".{$extension}", '', str_replace("{$imageFilename}-", '', $cachedFilename));

                $responsiveImage->addSource("{$responsiveImage->getUrlPath()}/{$cachedFilename}", $size);
            }

            return $responsiveImage;
        }

        return null;
    }

    /**
     * Create scaled image files and add them as sources to a Responsive Image, based on an array of file sizes:
     * [
     *      width => height,
     *      ...
     * ]
     *
     * @param SplFileInfo     $sourceImage
     * @param ResponsiveImage $responsiveImage
     *
     * @return ResponsiveImage
     */
    public function createScaledImages(SplFileInfo $sourceImage, ResponsiveImage $responsiveImage) : ResponsiveImage {
        $imageObject = $this->engine->make($sourceImage->getPathname());
        $urlPath = $responsiveImage->getUrlPath();
        $sizes = $this->scaler->scale($sourceImage, $imageObject);

        foreach ($sizes as $width => $height) {
            $scaledFileSrc = trim("{$urlPath}/{$imageObject->filename}-{$width}.{$imageObject->extension}", '/');
            $scaledFilePath = "{$this->getPublicPath()}/{$scaledFileSrc}";
            $responsiveImage->addSource($scaledFileSrc, $width);

            $scaledImage = $imageObject->resize((int) $width, (int) $height)->encode($imageObject->extension);

            if (!$this->enableCache || !$this->fs->exists($scaledFilePath)) {
                $this->fs->dumpFile($scaledFilePath, $scaledImage);
            }
        }

        $imageObject->destroy();

        if ($this->optimize) {
            $this->optimizeResponsiveImage($responsiveImage);
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
     * @param string $directory
     * @param string $path
     *
     * @return null|SplFileInfo
     * @throws FileNotFoundException
     */
    private function getImageFile(string $directory, string $path) {
        $path = ltrim($path, '/');
        $iterator = Finder::create()->files()->in($directory)->path($path)->getIterator();
        $iterator->rewind();

        $sourceImage = $iterator->current();

        if (!$sourceImage) {
            throw new FileNotFoundException("{$this->sourcePath}/{$path}");
        }

        return $sourceImage;
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
     * @return string
     */
    public function getPublicPath() : string {
        return $this->publicPath;
    }

    /**
     * @param mixed $optimizerOptions
     *
     * @return ResponsiveFactory
     */
    public function setOptimizerOptions($optimizerOptions) : ResponsiveFactory {
        $this->optimizerOptions = $optimizerOptions;

        return $this;
    }

    /**
     * @param bool $rebase
     *
     * @return ResponsiveFactory
     */
    public function setRebase(bool $rebase) : ResponsiveFactory {
        $this->rebase = $rebase;

        return $this;
    }

}
