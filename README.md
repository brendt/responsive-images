[![Build Status](https://scrutinizer-ci.com/g/brendt/responsive-images/badges/build.png?b=master)](https://scrutinizer-ci.com/g/brendt/responsive-images/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/brendt/responsive-images/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/brendt/responsive-images/?branch=master)

# Responsive Images

Automatically generate responsive images to work with the `srcset` and `sizes` spec. ([http://responsiveimages.org/](http://responsiveimages.org/))

```sh
composer require brendt/responsive-images
```

## Usage

```php
use Brendt\Image\ResponsiveFactory;
use Brendt\Image\Config\DefaultConfigurator;

$factory = new ResponsiveFactory(new DefaultConfigurator());
$image = $factory->create('img/image.jpeg');
```

```html
<img src="<?= $image->src() ?>" 
     srcset="<?= $image->srcset() ?>"/>
```

This sample would generate something like:

```hmtl
<img src="/image.jpeg" 
     srcset="/image-384.jpg 384w,
             /image-768.jpg 768w,
             /image-1152.jpg 1152w,
             /image-1536.jpg 1536w,
             /image.jpg 1920w" />
```

## Configuration

The `ResponsiveFactory` requires a `ResponsiveFactoryConfigurator` object which will set the needed parameters. 
A default configurator `DefaultConfigurator` is provider out of the box, and uses the following parameters:
 
```
[
    'driver'       => 'gd',
    'publicPath'   => './',
    'sourcePath'   => './',
    'enableCache'  => false,
    'stepModifier' => 0.5,
    'minFileSize'  => 10000,
    'minSize'      => 300,
    'minWidth'     => 150,
    'scaler'       => 'filesize',
]
```

You can override these parameters by providing and array to the `DefaultConfigurator`, 
or create a whole new configurator which implements `ResponsiveFactoryConfigurator`.

```
$factory = new ResponsiveFactory(new DefaultConfigurator([
    'driver'       => 'imagick',
    'sourcePath'   => './src',
    'publicPath'   => './public',
    'enableCache'  => true,
]));
```

### Paths

The `sourcePath` parameter is used to define where image source files are located. 
In case of the first example and above configuration, the image file should be save in `./src/img/image.jpeg`.

The `publicPath` parameter is used to save rendered images into. This path should be the public directory of your website.
The above example would render images into `./public/img/image.jpeg`. 

### All configuration options

- `driver`: the image driver to use. Defaults to `gd`. Possible options are `gd` or `imagick`.
- `sourcePath`: the path to load image source files. Defaults to `./`.
- `publicPath`: the path to render image files. Defaults to `./`.
- `enableCache`: enable or disable image caching. Enabling the cache wont' override existing images. Defaults to `false`.
- `stepModifier`: a percentage (between 0 and 1) which is used to create different image sizes. The higher this modifier, the more image variations will be rendered. Defaults to `0.8`.
- `minFileSize`: the minimum image filesize in bytes. Defaults to `10000`B (10KB).
- `minWidth`: the minimum image size in pixels. No images with size smaller than this number will be rendered. Defaults to `300` pixels.
- `scaler`: which scaler algorithm to use. Defaults to `filesize`. Possible options are `filesize` or `width`.
