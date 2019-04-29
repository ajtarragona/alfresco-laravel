<?php

if (! function_exists('get_object_public_vars')) {
	function get_object_public_vars($object) {
	    return get_object_vars($object);
	}
}


if (! function_exists('path_to_array')) {

	function path_to_array(&$arr, $path, $value, $separator='.') {
	    $keys = explode($separator, $path);

	    foreach ($keys as $key) {
	        $arr = &$arr[$key];
	    }
	    //dump($arr);
	    $arr = $value;
	}

}