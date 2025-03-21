# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mkinyua53/laravel-dicom.svg?style=flat-square)](https://packagist.org/packages/mkinyua53/laravel-dicom)
[![Total Downloads](https://img.shields.io/packagist/dt/mkinyua53/laravel-dicom.svg?style=flat-square)](https://packagist.org/packages/mkinyua53/laravel-dicom)

## Installation

You can install the package via composer:

```bash
composer require dionizas/laravel-dicom
```

## Usage

```php
// Usage description here
use Dionizas\LaravelDicom\LaravelDicom;

$dicom = new LaravelDicom;
$res = $dicom->parse($file) // $request->file('file')

if (! $res) {
  // failed to parse file
}

echo 'StudyDate : '.$dicom->getValue(0x0008, 0x0020)."\n";
echo 'Image Date : '.$dicom->getValue(0x0008, 0x0023)."\n";
echo 'Image Type : '.$dicom->getValue(0x0008, 0x0008)."\n";
echo 'Study Time : '.$dicom->getValue(0x0008, 0x0030)."\n";
echo 'Institution Name : '.$dicom->getValue(0x0008, 0x0080)."\n";
echo 'Manufacturer : '.$dicom->getValue(0x0008, 0x0070)."\n";
echo 'Manufacturer Model Name : '.$dicom->getValue(0x0008, 0x1090)."\n";
// or using element name
echo 'Patient Name : '.$dicom->getValue('PatientName')."\n";
echo 'Patient Age : '.$dicom->getValue('PatientAge')."\n";

// dump a PGM image from the file data
$res = $dicom->dumpImage('test.pgm');
if (! $res) {
    // failed to output image
}

```

### Testing
No tests defined yet.

```bash
# composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email mwatha.kinyua@hotmail.com instead of using the issue tracker.

## Credits

-   [Mwatha Kinyua](https://github.com/mkinyua53)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).

## Extras

Find me on X [@MKinyua53](https://twitter.com/mkinyua53)

[![Buy me a ☕️ ](https://media2.giphy.com/media/FoAQVAmLEsOz8DV2HS/100w.webp)](https://buymeacoffee.com/MKinyua53)
