[![Build Status](https://scrutinizer-ci.com/g/brendt/responsive-images/badges/build.png?b=master)](https://scrutinizer-ci.com/g/brendt/responsive-images/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/brendt/responsive-images/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/brendt/responsive-images/?branch=master)

# Responsive Images

Automatically generate responsive images to work with the `srcset` and `sizes` spec. ([http://responsiveimages.org/](http://responsiveimages.org/))

```sh
composer require brendt/responsive-images
```

## Usage

```php
use Brendt\Image\ResponsiveFactory;

$factory = new ResponsiveFactory();
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

The `ResponsiveFactory` can take a `ResponsiveFactoryConfigurator` object which will set the needed parameters. 
A default configurator `DefaultConfigurator` is provider out of the box, and uses the following parameters:
 
```php
[
    'driver'           => 'gd',
    'includeSource'    => true,
    'publicPath'       => './',
    'sourcePath'       => './',
    'rebase'           => false,
    'enableCache'      => false,
    'optimize'         => false,
    'scaler'           => 'filesize',
    'stepModifier'     => 0.5,
    'minFileSize'      => 5000,
    'maxFileSize'      => null,
    'minWidth'         => 300,
    'maxWidth'         => null,
    'sizes'            => [ 1920, 840, 300 ],
    'optimizerOptions' => [],
]
```

You can override these parameters by providing and array to the `DefaultConfigurator`, 
or create a whole new configurator which implements `ResponsiveFactoryConfigurator`.

```php
$factory = new ResponsiveFactory(new DefaultConfigurator([
    'driver'       => 'imagick',
    'sourcePath'   => './src',
    'publicPath'   => './public',
    'enableCache'  => true,
]));
```

### All configuration options

- `driver`: the image driver to use. Defaults to `gd`. Possible options are `gd` or `imagick`.
- `includeSource`: whether to include the source image in the `srcset`. Defaults to `true`.
- `sourcePath`: the path to load image source files. Defaults to `./`.
- `publicPath`: the path to render image files. Defaults to `./`.
- `rebase`: ignore the path of the requested image when searching in the source directory. Defaults to `false`.
- `enableCache`: enable or disable image caching. Enabling the cache wont' override existing images. Defaults to `false`.
- `optimize`: enable or disable the use of different optimizers (if installed on the system). Defaults to `false`.
- `scaler`: which scaler algorithm to use. Defaults to `filesize`. Possible options are `filesize`, `width` or `sizes`.
- `stepModifier`: a percentage (between 0 and 1) which is used to create different image sizes. The higher this modifier, the more image variations will be rendered. Defaults to `0.5`.
- `minFileSize`: the minimum image filesize in bytes. Defaults to `5000`B (5KB).
- `maxFileSize`: the maximum image filesize in bytes. Defaults to `null`.
- `minWidth`: the minimum image size in pixels. No images with size smaller than this number will be rendered. Defaults to `300` pixels.
- `maxWidth`: the maximum image size in pixels. No images with size smaller than this number will be rendered. Defaults to `null`.
- `sizes`: this parameter is used when the `sizes` scaler is enabled. This scaler will generate a fixed set of sizes, based on this array. 
 The expected values are the widths of the generated images. Defaults to `[]` (empty array). 
- `optimizerOptions`: options to pass to the image optimizer library. See [https://github.com/psliwa/image-optimizer](https://github.com/psliwa/image-optimizer) for more information.

### Paths

The `sourcePath` parameter is used to define where image source files are located. 
In case of the first example and above configuration, the image file should be saved in `./src/img/image.jpeg`.

The `publicPath` parameter is used to save rendered images into. This path should be the public directory of your website.
The above example would render images into `./public/img/image.jpeg`. 

#### Path rebasing

When the `rebase` option is enabled, source file lookup will differ: only the filename is used to search the file in the 
 source directory. An example would be the following.
 
```php
// Without rebase

$options = [
    'sourcePath' => './src/images',
    'publicPath' => './public',
];

$image = $factory->create('/img/responsive/image.jpeg');

// Source file is searched in './src/images/img/responsive/image.jpeg' 
// Public files are saved in './public/img/responsive/image-x.jpg'
``` 

```php
// With rebase

$options = [
    'sourcePath' => './src/images',
    'publicPath' => './public',
    'rebase'     => true,
];

$image = $factory->create('/img/responsive/image.jpeg');

// Source file is searched in './src/images/image.jpeg'  
// Public files are saved in './public/img/responsive/image-x.jpg'
```
