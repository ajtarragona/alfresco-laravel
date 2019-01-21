<?php
/**
 * Configuration for Alfresco-Laravel connection
 */
return [
		'siteid' => 'swsdp', //Site where the files will we uploaded
		'containerid' => 'uploads', //Folder where the files will we uploaded, must already exist in the site
		'url' => 'http://127.0.0.1:8080/alfresco', //URL of alfresco
		'user' => 'admin', //Username to acces alfresco
		'pass' => 'admin' //Password to access alfresco
	];