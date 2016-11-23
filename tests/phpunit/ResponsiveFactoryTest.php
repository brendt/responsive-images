<?php

namespace brendt\tests\phpunit;

use brendt\image\ResponsiveFactory;
use Symfony\Component\Filesystem\Filesystem;

class ResponsiveFactoryTest extends \PHPUnit_Framework_TestCase {

    public function test_create() {
        $compileDir = './tests/compile';
        $fs = new Filesystem();
        if ($fs->exists($compileDir)) {
            $fs->remove($compileDir);
        }

        $factory = new ResponsiveFactory($compileDir, 'gd', 0.2);

        $image = $factory->create('./tests/img/image.jpeg');

        $this->assertFalse($fs->exists("{$compileDir}/image-1920.jpeg"));
        $this->assertTrue($fs->exists("{$compileDir}/image-384.jpeg"));
        $this->assertTrue($fs->exists("{$compileDir}/image.jpeg"));

        $this->assertNotEmpty($image->srcset());
        $this->assertEquals("{$compileDir}/image.jpeg", $image->src());
    }

}
