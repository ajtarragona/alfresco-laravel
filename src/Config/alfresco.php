<?php
/**
 * Configuration for Alfresco-Laravel connection
 */

return [
	'url' => env('ALFRESCO_URL','http://127.0.0.1:8080/alfresco/'), //URL of alfresco
	'api' => env('ALFRESCO_API','cmis'), //cmis or rest
	'api_version' => env('ALFRESCO_API_VERSION','1.1'), //alfresco api version
	'repository_id' => env('ALFRESCO_REPOSITORY_ID','-default-'), //Repository where the files will we uploaded
	'base_id' => env('ALFRESCO_BASE_ID','12345678-1234-1234-1234-12345678900'), //Folder where the files will we uploaded, must already exist in the site
	'base_path' => env('ALFRESCO_BASE_PATH','/Sites'), //Folder where the files will we uploaded, must already exist in the site
	'user' => env('ALFRESCO_USER','admin'), //Username to acces alfresco
	'pass' => env('ALFRESCO_PASSWORD','admin'), //Password to access alfresco
	'repeated_policy' => env('ALFRESCO_REPEATED_POLICY','rename'), //rename or overwrite
	'debug' => env('ALFRESCO_DEBUG',false), //rename or overwrite
	'explorer' => env('ALFRESCO_EXPLORER',false),
	'verify_ssl' => env('ALFRESCO_VERIFY_SSL',false),
];

