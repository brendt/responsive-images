<?php

namespace Brendt\Image\Tests\Phpunit;

use Brendt\Image\Config\DefaultConfigurator;
use Brendt\Image\Config\ResponsiveFactoryConfigurator;
use Brendt\Image\ResponsiveFactory;
use Symfony\Component\Filesystem\Filesystem;

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
            'stepModifier' => 0.2,
            'scaler'       => 'width',
        ]);
    }

    public function test_create() {
        $factory = new ResponsiveFactory($this->configurator);
        $image = $factory->create('img/image.jpeg');

        $this->assertTrue($this->fs->exists("{$this->publicPath}/img/image-384.jpeg"));
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
        $this->assertContains('/img/image-384.jpeg', $srcset);
        $this->assertContains('/img/image-1152.jpeg', $srcset);
    }

    public function test_create_sets_default_srcset() {
        $factory = new ResponsiveFactory($this->configurator);
        $url = 'img/image.jpeg';
        $image = $factory->create($url);

        $srcset = $image->srcset();

        $this->assertContains('/img/image.jpeg 1920w', $srcset);
    }
}
