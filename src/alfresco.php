<?php
/**
 * Configuration for Alfresco-Laravel connection
 */
return [
		'url' => 'http://127.0.0.1:8080/alfresco', //URL of alfresco
		'api' => '', //CMIS API
		'repository_id' => '-default-', //Repository where the files will we uploaded
		'siteid' => 'swsdp', //Site where the files will we uploaded
		'containerid' => 'uploads', //Folder where the files will we uploaded, must already exist in the site
		'user' => 'admin', //Username to acces alfresco
		'pass' => 'admin' //Password to access alfresco
	];