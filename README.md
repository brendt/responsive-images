[![Build Status](https://scrutinizer-ci.com/g/brendt/responsive-images/badges/build.png?b=master)](https://scrutinizer-ci.com/g/brendt/responsive-images/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/brendt/responsive-images/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/brendt/responsive-images/?branch=master)

# Responsive Images

Automatically generate responsive images to work with the `srcset` and `sizes` spec. ([http://responsiveimages.org/](http://responsiveimages.org/))

```sh
composer require brendt/responsive-images
```

## Usage

```php
use brendt\image\ResponsiveFactory;

$public = './public/img';
$factory = new ResponsiveFactory($public);

$image = $factory->create('./data/img/image.jpeg');
$image->addSizes([
    'min-width: 920px' => '50vw',
    'min-width: 1200px' => '33vw',
    '100vw',
]);
```

```html
<img src="<?= $image->src() ?>" 
     srcset="<?= $image->srcset() ?>"
     sizes="<?= $image->sizes() ?>" />
```

This sample would generate something like:

```hmtl
<img src="./public/img/image.jpeg" 
     srcset="./public/img/image-384.jpg 384w,
             ./public/img/image-768.jpg 768w,
             ./public/img/image-1152.jpg 1152w,
             ./public/img/image-1536.jpg 1536w,
             ./public/img/image.jpg 1920w"
     sizes="(min-width: 920px) 50vw,
            (min-width: 1200px) 33w,
            100vw" />
```

**Note**: `sizes` should never be hardcoded like this example. They should probably come from a configuration file or frontend. 

