<?php

namespace Brendt\Image\Exception;

class FileNotFoundException extends \Exception
{

    public function __construct($path) {
        parent::__construct("Could not find the image in path {$path}. Does the file exist and does it have the correct permissions?");
    }

}
