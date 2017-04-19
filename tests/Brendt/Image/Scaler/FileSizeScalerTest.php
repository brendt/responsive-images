<?php

namespace Brendt\Image\Tests\Phpunit\Scaler;

use Brendt\Image\Config\DefaultConfigurator;
use Brendt\Image\Scaler\FileSizeScaler;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileSizeScalerTest extends TestCase
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
     * @var FileSizeScaler
     */
    private $scaler;

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

    public function setUp() {
        $this->scaler = new FileSizeScaler(new DefaultConfigurator([
            'publicPath' => $this->publicPath,
        ]));
    }

    public function test_scale_down() {
        $sourceFile = $this->createSourceFile();
        $imageObject = $this->createImageObject();

        $sizes = $this->scaler->scale($sourceFile, $imageObject);

        $this->assertTrue(count($sizes) > 1);
    }

    public function test_scale_down_with_max_width() {
        $sourceFile = $this->createSourceFile();
        $imageObject = $this->createImageObject();

        $this->scaler->setMaxWidth(1000);
        $sizes = $this->scaler->scale($sourceFile, $imageObject);

        foreach ($sizes as $width => $height) {
            $this->assertTrue($width < 1000);
        }
    }

    public function test_scale_down_with_max_filesize() {
        $sourceFile = $this->createSourceFile();
        $imageObject = $this->createImageObject();

        $this->scaler->setMaxFileSize(100000);
        $sizes = $this->scaler->scale($sourceFile, $imageObject);

        $this->assertCount(3, $sizes);
    }

    public function test_scale_down_with_include_source_disabled() {
        $sourceFile = $this->createSourceFile();
        $imageObject = $this->createImageObject();

        $this->scaler->setIncludeSource(false);
        $sizes = $this->scaler->scale($sourceFile, $imageObject);

        $this->assertFalse(array_key_exists(1920, $sizes));
    }

    // TODO: test algorithm

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
