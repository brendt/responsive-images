<?php

namespace brendt\tests\phpunit;

use brendt\image\ResponsiveImage;

class ResponsiveImageTest extends \PHPUnit_Framework_TestCase {

    public function test_construct() {
        $image = new ResponsiveImage('tests/img/image.jpeg');

        $this->assertEquals('/tests/img/image.jpeg', $image->getSrc());
        $this->assertEquals('tests/img/image.jpeg', $image->getFile()->getRelativePathName());
    }

    /**
     * @expectedException brendt\image\exception\FileNotFoundException
     */
    public function test_construct_throws_file_not_found_exception() {
        new ResponsiveImage('tests/img/unknown.jpeg');
    }

    public function test_src() {
        $image = new ResponsiveImage('tests/img/image.jpeg');

        $this->assertEquals('/tests/img/image.jpeg', $image->src());
    }

    public function test_srcset() {
        $image = new ResponsiveImage('tests/img/image.jpeg');

        $this->assertEquals('', $image->srcset());

        $image->addSource('/img/test-500.jpg', '500px');
        $image->addSource([
            'img/test.jpg' => 1920,
            'img/test-300' => 300,
        ]);

        $this->assertEquals('/img/test.jpg 1920w,/img/test-500.jpg 500w,/img/test-300 300w', $image->srcset());
    }

    public function test_sizes() {
        $image = new ResponsiveImage('tests/img/image.jpeg');

        $this->assertEquals('', $image->sizes());

        $image->addSizes('min-width: 650px', '33vw');
        $image->addSizes([
            'min-width: 1000px' => '50vw',
            '100vw'
        ]);

        $this->assertEquals('(min-width: 650px) 33vw, (min-width: 1000px) 50vw, 100vw', $image->sizes());
    }

}
