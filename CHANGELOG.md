# Changelog

## 1.2.0

- Update minimum requirements to PHP 7.1.
- Refactor scalers to only calculate file sizes and not handle image saving.
- Add `optimize` parameter to run image optimizers.

## 1.1.2

- Improve caching, scalers are now not used when images are already cached.
- Set default minFileSize to 5 KB.

## 1.1

- Add Scaler support
- Add FileSize scaler as default.

To use the old scaler:

```php
$factory = new ResponsiveFactory(new DefaultConfigurator([
    'scaler' => 'width',
]));
```

The new Scaler is called `filesize` and set by default, this scaler differs from the width scaler because it scales down 
 based on file size instead of the width of an image.

```php
$factory = new ResponsiveFactory(new DefaultConfigurator([
    'scaler' => 'filesize',
]));
```

**Note**: the `stepModifier` parameter is now used different, the higher this modifier, the more images will be generated.

## 1.0

- Use PSR naming standards.
