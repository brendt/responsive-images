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
    'driver'       => 'gd',
    'publicPath'   => './',
    'sourcePath'   => './',
    'enableCache'  => false,
    'optimize'     => false, 
    'async'        => false,
    'scaler'       => 'filesize',
    'stepModifier' => 0.5,
    'minFileSize'  => 10000,
    'minSize'      => 300,
    'minWidth'     => 150,
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

### Async

If you're creating more than one image at a time, eg. in a loop when parsing a page; you might want to take a look at the `async` option.
 
#### Prerequisites

- PHP's `pcntl` extension has to be installed: [http://php.net/manual/en/book.pcntl.php](http://php.net/manual/en/book.pcntl.php).
- The `async` option must be set to true.

If any of those requirements aren't met, the ResponsiveFactory will fall back to using a single process. So it will never
 fail, but it will be slower to render images.
 
#### Implementation

You'll have to change two things in your code when rendering images asynchronous.

- Add a callback on the responsive image `onSave`.
- At the end of all code, wait for all responsive images' promise to be fulfilled.

```php
$factory = new ResponsiveFactory(new DefaultConfigurator([
    'async' => true,
]));

$responsiveImage = $factory->create($url);
$responsiveImage->onSave(function () use ($responsiveImage) {
    // The files of this image are saved.
});

\Amp\wait($responsiveImage->getPromise());
```

A more real world example would include multiple responsive images.

```php
// Create the factory and create multiple images, which are added to $images.

// Do other things

$promises = [];

foreach ($images as $image) {
    $promises[] = $image->getPromise();
}

$mainPromise = \Amp\all($promises);
\Amp\wait($mainPromise);
```

The benefit of this approach is that you can do other things instead of having to wait for images to finish rendering.
 You could eg. start loading data from a database or render HTML pages (because the src, srcset and sizes of the
 ResponsiveImage are already set).

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
- `optimize`: enable or disable the use of different optimizers (if installed on the system). Defaults to `false`.
- `async`: enable or disable asynchronous image rendering. Defaults to `false`.
- `scaler`: which scaler algorithm to use. Defaults to `filesize`. Possible options are `filesize` or `width`.
- `stepModifier`: a percentage (between 0 and 1) which is used to create different image sizes. The higher this modifier, the more image variations will be rendered. Defaults to `0.8`.
- `minFileSize`: the minimum image filesize in bytes. Defaults to `10000`B (10KB).
- `minWidth`: the minimum image size in pixels. No images with size smaller than this number will be rendered. Defaults to `300` pixels.
