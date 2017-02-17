# Changelog

## 1.2

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

## 1.1

- Use PSR naming standards.
