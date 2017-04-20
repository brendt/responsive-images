<?php

namespace Brendt\Image\Tests\Phpunit;

use Brendt\Image\Config\DefaultConfigurator;
use Brendt\Image\Config\ResponsiveFactoryConfigurator;
use Brendt\Image\ResponsiveFactory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ResponsiveFactoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ResponsiveFactoryConfigurator
     */
    private $configurator;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    public $publicPath = './tests/public';

    public function __construct() {
        parent::__construct();

        $this->fs = new Filesystem();
    }

    protected function tearDown() {
        if ($this->fs->exists($this->publicPath)) {
            $this->fs->remove($this->publicPath);
        }
    }

    public function setUp() {
        $this->configurator = new DefaultConfigurator([
            'publicPath'   => $this->publicPath,
            'engine'       => 'gd',
            'stepModifier' => 0.5,
            'scaler'       => 'width',
        ]);
    }

    public function test_simple_construct() {
        new ResponsiveFactory();
    }

    public function test_create() {
        $factory = new ResponsiveFactory($this->configurator);
        $image = $factory->create('img/image.jpeg');

        $this->assertTrue($this->fs->exists("{$this->publicPath}/img/image.jpeg"));

        $this->assertNotEmpty($image->srcset());
    }

    public function test_create_sets_correct_src() {
        $factory = new ResponsiveFactory($this->configurator);
        $url = 'img/image.jpeg';
        $image = $factory->create($url);

        $this->assertEquals("/{$url}", $image->src());
    }

    public function test_create_doesnt_render_full_width_srcset() {
        $factory = new ResponsiveFactory($this->configurator);
        $url = 'img/image.jpeg';
        $publicPath = $this->configurator->getConfig()['publicPath'];
        $factory->create($url);

        $this->assertFalse($this->fs->exists("{$publicPath}/image-1920.jpeg"));
    }

    public function test_create_sets_correct_srcset() {
        $factory = new ResponsiveFactory($this->configurator);
        $url = 'img/image.jpeg';
        $image = $factory->create($url);

        $srcset = $image->srcset();

        $this->assertNotEmpty($srcset);
    }

    public function test_create_sets_default_srcset() {
        $factory = new ResponsiveFactory($this->configurator);
        $url = 'img/image.jpeg';
        $image = $factory->create($url);

        $srcset = $image->srcset();

        $this->assertContains('/img/image-1920.jpeg 1920w', $srcset);
    }

    public function test_optimizer() {
        $url = 'img/image.jpeg';

        $normalFactory = new ResponsiveFactory($this->configurator);
        $optimizedFactory = new ResponsiveFactory(new DefaultConfigurator([
            'publicPath'   => $this->publicPath,
            'engine'       => 'gd',
            'stepModifier' => 0.5,
            'scaler'       => 'width',
            'optimize'     => true,
            'enableCache'  => false,
        ]));

        $normalImage = $normalFactory->create($url);
        $normalImageFiles = Finder::create()->files()->in($this->publicPath)->path(trim($normalImage->getSrc(), '/'))->getIterator();
        $normalImageFiles->rewind();
        /** @var SplFileInfo $normalImageFile */
        $normalImageFile = $normalImageFiles->current();
        $normalImageFileSize = $normalImageFile->getSize();

        $optimizedImage = $optimizedFactory->create($url);
        $optimizedImageFiles = Finder::create()->files()->in($this->publicPath)->path(trim($optimizedImage->getSrc(), '/'))->getIterator();
        $optimizedImageFiles->rewind();
        /** @var SplFileInfo $optimizedImageFile */
        $optimizedImageFile = $optimizedImageFiles->current();
        $optimizedImageFileSize = $optimizedImageFile->getSize();

        $this->assertTrue($optimizedImageFileSize <= $normalImageFileSize);
    }

    /**
     * @test
     */
    public function test_rebase() {
        $configurator = new DefaultConfigurator([
            'publicPath'   => './tests/public',
            'sourcePath'   => './tests/img',
            'rebase'       => true,
            'engine'       => 'gd',
            'stepModifier' => 0.5,
            'scaler'       => 'width',
        ]);

        $responsiveFactory = new ResponsiveFactory($configurator);

        $image = $responsiveFactory->create('/img/responsive/image.jpeg');

        $this->assertTrue($this->fs->exists('./tests/public/img/responsive/image.jpeg'));
        $this->assertEquals('/img/responsive/image.jpeg', $image->src());
        $this->assertContains('/img/responsive', $image->srcset());
    }

    public function test_cached_result() {
        $src = file_get_contents('./tests/img/image.jpeg');
        
        if (!$this->fs->exists('./tests/public/img')) {
            $this->fs->mkdir('./tests/public/img');
        }

        $this->fs->dumpFile('./tests/public/img/image.jpeg', $src);
        $this->fs->dumpFile('./tests/public/img/image-500.jpeg', $src);
        $this->fs->dumpFile('./tests/public/img/image-1000.jpeg', $src);

        $factory = new ResponsiveFactory(new DefaultConfigurator([
            'publicPath'   => './tests/public',
            'sourcePath'   => './tests',
            'enableCache' => true
        ]));
        $image = $factory->create('/img/image.jpeg');

        $srcset = $image->getSrcset();
        $this->assertArrayHasKey(500, $srcset);
        $this->assertArrayHasKey(1000, $srcset);
    }
}
