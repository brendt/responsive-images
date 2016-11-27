[![Build Status](https://scrutinizer-ci.com/g/brendt/responsive-images/badges/build.png?b=master)](https://scrutinizer-ci.com/g/brendt/responsive-images/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/brendt/responsive-images/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/brendt/responsive-images/?branch=master)

# Responsive Images

Automatically generate responsive images to work with the `srcset` and `sizes` spec. ([http://responsiveimages.org/](http://responsiveimages.org/))

```sh
composer require brendt/responsive-images
```

## Usage

```php
use brendt\image\ResponsiveFactory;
use brendt\image\config\DefaultConfigurator;

$factory = new ResponsiveFactory(new DefaultConfigurator());
$image = $factory->create('img/image.jpeg');
```

```html
<img src="<?= $image->src() ?>" 
     srcset="<?= $image->srcset() ?>"/>
```

This sample would generate something like:

```hmtl
<img    
        src="/public/img/image.jpeg" 
     srcset="/public/img/image-384.jpg 384w,
             /public/img/image-768.jpg 768w,
             /public/img/image-1152.jpg 1152w,
             /public/img/image-1536.jpg 1536w,
             /public/img/image.jpg 1920w" />
```


