<?php

if (! function_exists('isJson')) {
	function isJson($string) {
	 	try{
			if(!is_string($string)) return false;
			$ret=json_decode($string);
			if(!is_array($ret) && !is_object($ret)) return false; //es un tipo simple
			 
			return (json_last_error() == JSON_ERROR_NONE);
		}catch(Exception $e){
			return false;
		}
	}
}

if (! function_exists('to_object')) {
	function to_object($array) {
	 	return json_decode(json_encode($array), FALSE);

	}
}
if (! function_exists('to_array')) {
	function to_array($object) {
	 	return json_decode(json_encode($object), true);
	}
}
