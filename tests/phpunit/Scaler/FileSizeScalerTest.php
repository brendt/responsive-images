<?php

namespace Brendt\Image\Tests\Phpunit\Scaler;

use Brendt\Image\Config\DefaultConfigurator;
use Brendt\Image\ResponsiveImage;
use Brendt\Image\Scaler\FileSizeScaler;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

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
        $responsiveImage = $this->createResponsiveImage();
        $imageObject = $this->createImageObject();

        $responsiveImage = $this->scaler->scale($responsiveImage, $imageObject);

        $this->assertTrue(count($responsiveImage->getSrcset()) > 1);
    }

    // TODO: test algorithm

    private function createResponsiveImage() {
        $responsiveImage = new ResponsiveImage('img/image.jpeg');
        $responsiveImage->setExtension('jpeg');
        $responsiveImage->setFileName('image');
        $responsiveImage->setUrlPath('/img');

        return $responsiveImage;
    }

    private function createImageObject() {
        $imageObject = $this->engine->make('./tests/img/image.jpeg');

        return $imageObject;
    }

}
