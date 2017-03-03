<?php

namespace Brendt\Image\Tests\Phpunit;

use Amp\Parallel\Forking\Fork;
use AsyncInterop\Loop;
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
    private $publicPath = './tests/public';

    public function __construct() {
        parent::__construct();

        $this->fs = new Filesystem();
    }

    public function __destruct() {
        if ($this->fs->exists($this->publicPath)) {
            $this->fs->remove($this->publicPath);
        }
    }

    public function setUp() {
        if ($this->fs->exists($this->publicPath)) {
            $this->fs->remove($this->publicPath);
        }

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

        $this->assertContains('/img/image.jpeg 1920w', $srcset);
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

    public function test_async() {
        $url = 'img/image.jpeg';
        $factory = new ResponsiveFactory(new DefaultConfigurator([
            'publicPath'   => $this->publicPath,
            'engine'       => 'gd',
            'stepModifier' => 0.5,
            'scaler'       => 'filesize',
            'enableCache'  => false,
            'async'        => true,
        ]));

        $responsiveImage = $factory->create($url);

        $this->assertTrue(count($responsiveImage->getSrcset()) > 1);
        $this->assertEquals("/{$url}", $responsiveImage->src());

        $testCase = $this;
        $responsiveImage->onSaved(function () use ($testCase, $responsiveImage) {
            $fs = new Filesystem();

            foreach ($responsiveImage->getSrcset() as $src) {
                $src = trim($src, '/');

                $testCase->assertTrue($fs->exists("{$testCase->publicPath}/{$src}"));
            }
        });

        \Amp\wait($responsiveImage->getPromise());
    }
}
