<?php

namespace Brendt\Image\Scaler;

use Brendt\Image\Config\DefaultConfigurator;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SizesScalerTest extends TestCase
{

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $publicPath = './tests/public';

    /**
     * @var ImageManager
     */
    private $engine;


    public function __construct() {
        parent::__construct();

        $this->fs = new Filesystem();
        $this->engine = new ImageManager([
            'driver' => 'gd',
        ]);
    }

    public function __destruct() {
        if ($this->fs->exists($this->publicPath)) {
            $this->fs->remove($this->publicPath);
        }
    }

    public function test_scale_down() {
        $scaler = new SizesScaler(new DefaultConfigurator([
            'publicPath'    => $this->publicPath,
            'includeSource' => false,
        ]));
        $scaler->setSizes([500, 10000, 1920]);

        $sourceFile = $this->createSourceFile();
        $imageObject = $this->createImageObject();

        $sizes = $scaler->scale($sourceFile, $imageObject);

        $this->assertCount(2, $sizes);
    }

    public function test_scale_down_with_include_source() {
        $scaler = new SizesScaler(new DefaultConfigurator([
            'publicPath'    => $this->publicPath,
            'includeSource' => true,
        ]));
        $scaler->setSizes([500, 800]);

        $sourceFile = $this->createSourceFile();
        $imageObject = $this->createImageObject();

        $sizes = $scaler->scale($sourceFile, $imageObject);
        
        $this->assertCount(3, $sizes);
    }


    private function createImageObject() {
        $imageObject = $this->engine->make('./tests/img/image.jpeg');

        return $imageObject;
    }

    private function createSourceFile() {
        $sourceFiles = Finder::create()->files()->in('./tests/img')->name('image.jpeg')->getIterator();
        $sourceFiles->rewind();

        return $sourceFiles->current();
    }
}
