<?php

namespace Brendt\Image\Tests\Phpunit;

use Brendt\Image\ResponsiveImage;

class ResponsiveImageTest extends \PHPUnit_Framework_TestCase
{

    public function test_construct() {
        new ResponsiveImage('img/image.jpeg');
    }

    public function test_src() {
        $image = new ResponsiveImage('img/image.jpeg');

        $this->assertEquals('/img/image.jpeg', $image->src());
    }

    public function test_src_with_slash() {
        $image = new ResponsiveImage('/img/image.jpeg');

        $this->assertEquals('/img/image.jpeg', $image->src());
    }

    public function test_srcset_empty_on_construct() {
        $image = new ResponsiveImage('img/image.jpeg');

        $this->assertEquals('', $image->srcset());
    }

    public function test_srcset_add_single_source() {
        $image = new ResponsiveImage('img/image.jpeg');
        $image->addSource('img/test-500.jpg', '500px');

        $this->assertEquals('/img/test-500.jpg 500w', $image->srcset());
    }

    public function test_srcset_add_multiple_sources() {
        $image = new ResponsiveImage('img/image.jpeg');

        $image->addSource([
            'img/test.jpg'     => 1920,
            'img/test-300.jpg' => 300,
        ]);

        $this->assertEquals('/img/test.jpg 1920w,/img/test-300.jpg 300w', $image->srcset());
    }

    public function test_sizes_empty_on_construct() {
        $image = new ResponsiveImage('img/image.jpeg');

        $this->assertEquals('', $image->sizes());
    }

    public function test_sizes_add_single_size() {
        $image = new ResponsiveImage('tests/img/image.jpeg');
        $image->addSizes('min-width: 650px', '33vw');

        $this->assertEquals('(min-width: 650px) 33vw', $image->sizes());
    }

    public function test_sizes_add_multiple_sizes() {
        $image = new ResponsiveImage('tests/img/image.jpeg');

        $image->addSizes([
            'min-width: 1000px' => '50vw',
            '100vw',
        ]);

        $this->assertEquals('(min-width: 1000px) 50vw, 100vw', $image->sizes());
    }

}
