# Changelog

## 1.9.1

- Support PHP 7.0

## 1.9.0

- Refactor code directories.

## 1.8.2

- Cleanup Responsive Factory code.
- Add caching test.

## 1.8.1

- Bugfix for extension of cached files not correctly loading.

## 1.8.0

- Add `includeSource` parameter.

## 1.7.3

- Bugfix for invalid cache path when rebase was enabled.

## 1.7.2

- Bugfix for wrong composer sources.

## 1.7.1

- Bugfix for srcset not correctly rendering.

## 1.7.0

- Add `sizes` scaler. This scaler can be configured to generate a fixed set of images based on pre-defined widths, 
 provided via the `sizes` parameter. See the README for more information.

## 1.6.0

- Add `rebase` parameter.
- Add `maxFileSize` parameter.
- Add `maxWidth` parameter.

## 1.5.1

- Fix a bug so that unkown file exceptions are correctly thrown.

## 1.5.0

- Removed AMP dependencies.

## 1.4.1

- Temporary remove Amp because of BC breaking changes with each update.

## 1.4.0

- Add optimizer options support

## 1.3.2

- Add fixed amphp versions because of BC breaking changes in their library.

## 1.3.1

- Several bug fixes and optimizations when running enabling the `async` option.

## 1.3.0

- Add `async` option to downscale images in separate processes. PHP's `pcntl` extension is required for this to work.

## 1.2.2

- Add simple construct for the ResponsiveFactory.

## 1.2.1

- Fixed bug with the cache not being searched in the public path.

## 1.2.0

- Update minimum requirements to PHP 7.1.
- Refactor scalers to only calculate file sizes and not handle image saving.
- Add `optimize` parameter to run image optimizers. Defaults to `false`.

## 1.1.2

- Improve caching, scalers are now not used when images are already cached.
- Set default `minFileSize` to 5 KB.

## 1.1.0

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

## 1.0.0

- Use PSR naming standards.
