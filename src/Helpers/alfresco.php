<?php
if (! function_exists('alfresco')) {
	function alfresco($options=null){
		return new \Ajtarragona\AlfrescoLaravel\Models\AlfrescoService($options);
	}
}