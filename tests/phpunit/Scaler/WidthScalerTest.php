<?php

namespace Brendt\Image\Tests\Phpunit\Scaler;

use Brendt\Image\Config\DefaultConfigurator;
use Brendt\Image\ResponsiveImage;
use Brendt\Image\Scaler\WidthScaler;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class WidthScalerTest extends TestCase
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
     * @var WidthScaler
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
        $this->scaler = new WidthScaler(new DefaultConfigurator([
            'scaler' => 'width',
            'stepModifier' => 0.8,
            'publicPath' => $this->publicPath
        ]));
    }

    public function test_scale_down() {
        $sourceFile = $this->createSourceFile();
        $imageObject = $this->createImageObject();

        $sizes = $this->scaler->scale($sourceFile, $imageObject);

        $this->assertTrue(count($sizes) > 1);
    }

    // TODO: test algorithm

    private function createSourceFile() {
        $sourceFiles = Finder::create()->files()->in('./tests/img')->name('image.jpeg')->getIterator();
        $sourceFiles->rewind();

        return $sourceFiles->current();
    }

    private function createImageObject() {
        $imageObject = $this->engine->make('./tests/img/image.jpeg');

        return $imageObject;
    }

}
