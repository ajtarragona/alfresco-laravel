# Alfresco file manager for Laravel 5.5

## Alfresco-Laravel

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

This function will return a boolean indicating the result of the operation.

### List folder content

To list the content of a folder in Alfresco, you need to add the following lines to your code:
```php
use Alfresco; // At the top of your controller
------
Alfresco::list($nodeId); //When you want to list the content of a folder, being $nodeId the id of the folder to list
```

The return of this function will be something like this:
```php
[
	"back" => "b4cff62a-664d-4d45-9302-98723eac1319", //The id of the parent folder (optional)
	"children" => [ //Array with all the childs of the folder (optional)
					[
						"id":"b31cfcd4-06a8-4a8e-8073-2b047aa2f82a", //The id of the child
						"name":"image1.png", //The name of the document/folder
						"isFolder":false //Boolean to indicate if the node is a folder or not
					],
					[
						"id":"a6b424ec-48b5-47b0-b42a-73785ed3d487",
						"name":"image2.jpg",
						"isFolder":false
					],
					[
						"id":"f2cb8696-a9a3-49d8-bd16-5960cb0c2948",
						"name":"document.pdf",
						"isFolder":false
					],
					[
						"id":"f1ba047c-d9b1-4554-aa56-7004f7327cf5",
						"name":"test",
						"isFolder":true
					]
				]
]
```

### Download

To download a file from Alfresco, you need to add the following lines to your code:
```php
use Alfresco; // At the top of your controller
------
Alfresco::download($nodeId, $destinationFolder); //When you want to download a file, being $nodeId the id of the node to download and $destinationFolder the route to the folder where de node will be storaged
```

This function will return a boolean indicating the result of the operation.
