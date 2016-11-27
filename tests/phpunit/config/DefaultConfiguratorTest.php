<?php

namespace brendt\tests\phpunit\config;

use brendt\image\config\DefaultConfigurator;

class DefaultConfiguratorTest extends \PHPUnit_Framework_TestCase {

    public function test_default_construct() {
        new DefaultConfigurator();
    }

    public function test_construct_merges_config() {
        $configurator = new DefaultConfigurator([
            'enableCache' => true
        ]);

        $this->assertTrue($configurator->getConfig()['enableCache']);
        $this->assertEquals('gd', $configurator->getConfig()['driver']);
    }

    /**
     * @expectedException brendt\image\exception\InvalidConfigurationException
     */
    public function test_construct_throws_exception_with_unknown_driver() {
        new DefaultConfigurator([
            'driver' => 'unknown'
        ]);
    }

}
