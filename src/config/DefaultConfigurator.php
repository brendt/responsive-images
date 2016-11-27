<?php

namespace brendt\image\config;

use brendt\image\exception\InvalidConfigurationException;
use brendt\image\ResponsiveFactory;

class DefaultConfigurator implements ResponsiveFactoryConfigurator {

    protected $config = [
        'driver'       => 'gd',
        'publicPath'   => './',
        'sourcePath'   => './',
        'enableCache'  => false,
        'stepModifier' => 0.1,
        'minSize'      => 300,
    ];

    /**
     * ResponsiveFactoryConfigurator constructor.
     *
     * @param array $config
     *
     * @throws InvalidConfigurationException
     */
    public function __construct(array $config = []) {
        if (isset($config['driver']) && !in_array($config['driver'], ['gd', 'imagick'])) {
            throw new InvalidConfigurationException('Invalid driver. Possible drivers are `gd` and `imagick`');
        }

        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param ResponsiveFactory $factory
     *
     * @return void
     */
    public function configure(ResponsiveFactory $factory) {
        $factory
            ->setDriver($this->config['driver'])
            ->setPublicPath($this->config['publicPath'])
            ->setSourcePath($this->config['sourcePath'])
            ->setEnableCache($this->config['enableCache'])
            ->setStepModifier($this->config['stepModifier'])
            ->setMinSize($this->config['minSize']);
    }

    /**
     * @return array
     */
    public function getConfig() {
        return $this->config;
    }
}
