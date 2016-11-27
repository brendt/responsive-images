<?php

namespace brendt\image\config;

use brendt\image\ResponsiveFactory;

interface ResponsiveFactoryConfigurator {

    /**
     * ResponsiveFactoryConfigurator constructor.
     *
     * @param array $config
     */
    public function __construct(array $config);

    /**
     * @param ResponsiveFactory $factory
     *
     * @return mixed
     */
    public function configure(ResponsiveFactory $factory);

    /**
     * @return array
     */
    public function getConfig();

}
