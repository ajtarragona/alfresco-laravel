# Alfresco file manager for Laravel 5.5

## Alfresco-Laravel

[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Alfresco-Laravel is a package that allows the management of files in a Alfresco via Laravel.


## Installation

```bash
composer require ajtarragona/alfresco-laravel:"@dev"
```

## Service Provider

After the installation, you need to register Alfresco-Laravel in your `config/app.php` file, find the providers array and add:

```php
Ajtarragona\AlfrescoLaravel\AlfrescoLaravelServiceProvider::class,
```

## Alias

For a simpler use of this package, register the alias in the alias array in your `config/app.php` file adding:

```php
'Alfresco' => Ajtarragona\AlfrescoLaravel\Models\AlfrescoLaravel::class
```

## Configuration

Finally you must publish the configuration file to set your connection data.

```bash
php artisan vendor:publish --tag=alfresco
```

This will copy the configuration file to `config/alfresco.php`.

## Usage

After the configuration, the package will we ready to use:

### Upload

To upload a file to Alfresco, you need to add the following lines to your code:
```php
use Alfresco; // At the top of your controller
------
Alfresco::upload($file); //When you want to upload a file, being $file a UploadedFile instance
```

[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/ajtarragona/alfresco-laravel.svg?style=flat-square
[link-downloads]: https://packagist.org/packages/ajtarragona/alfresco-laravel