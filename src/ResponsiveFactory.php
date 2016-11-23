<?php

namespace brendt\image;

use brendt\image\exception\FileNotFoundException;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ResponsiveFactory {

    /**
     * The compile directory to store images.
     *
     * @var string
     */
    protected $compileDir;

    /**
     * @var float
     */
    protected $stepModified = 0.1;

    /**
     * @var integer
     */
    protected $minsize = 300;

    /**
     * @var ImageManager
     */
    protected $engine;

    /**
     * ResponsiveFactory constructor.
     *
     * @param        $compileDir
     * @param string $driver
     * @param float  $stepModifier
     * @param int    $minsize
     */
    public function __construct($compileDir, $driver = 'gd', $stepModifier = 0.1, $minsize = 300) {
        $this->compileDir = './' . trim($compileDir, './');

        $fs = new Filesystem();
        if (!$fs->exists($this->compileDir)) {
            $fs->mkdir($this->compileDir);
        }

        $this->engine = new ImageManager([
            'driver' => $driver,
        ]);

        $this->stepModifier = $stepModifier;
        $this->minsize = $minsize;
    }

    /**
     * @param $path
     *
     * @return ResponsiveImage
     * @throws FileNotFoundException
     */
    public function create($path) {
        $file = $this->getImageFile($path);
        $sourcePath = "{$this->compileDir}/{$file->getFilename()}";

        $fs = new Filesystem();
        if (!$fs->exists($sourcePath)) {
            $fs->copy($path, $sourcePath);
        }

        $sourceImage = new ResponsiveImage($sourcePath);
        $sourceFile = $sourceImage->getFile();

        try {
            $image = $this->engine->make($sourceFile->getPathname());
        } catch (NotReadableException $e) {
            throw new FileNotFoundException($sourceFile->getPathname());
        }

        $extension = $sourceFile->getExtension();
        $name = str_replace(".{$extension}", '', $sourceFile->getFilename());

        $width = $image->getWidth();
        $stepWidth = $width * $this->stepModifier;
        $height = $image->getHeight();
        $stepHeight = $height * $this->stepModifier;

        while ($width >= $this->minsize) {
            if ($width === $image->getWidth()) {
                $width -= $stepWidth;
                $height -= $stepHeight;

                continue;
            }

            $scaledPath = "{$this->compileDir}/{$name}-{$width}.{$extension}";

            $image->resize($width, $height)
                ->save($scaledPath);

            $sourceImage->addSource($scaledPath, $width);

            $width -= $stepWidth;
            $height -= $stepHeight;
        }

        return $sourceImage;
    }

    /**
     * @param int $minsize
     *
     * @return ResponsiveFactory
     */
    public function setMinsize($minsize) {
        $this->minsize = $minsize;

        return $this;
    }

    /**
     * @param float $stepModified
     *
     * @return ResponsiveFactory
     */
    public function setStepModified($stepModified) {
        $this->stepModified = $stepModified;

        return $this;
    }

    /**
     * @param $src
     *
     * @return SplFileInfo
     * @throws FileNotFoundException
     */
    private function getImageFile($src) {
        $files = Finder::create()->files()->in('.')->path(trim($src, './'));

        foreach ($files as $file) {
            return $file;
        }

        throw new FileNotFoundException($src);
    }

}
