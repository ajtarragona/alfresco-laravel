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
if (! function_exists('icon')) {

	function icon($name){
		return '<i class="'.$name.'"></i>';
	}
}


if (! function_exists('makeLinks')) {
	function makeLinks($str, $linkname=False,$attributes=[]) {
		$hostRegex = "([a-z\d][-a-z\d]*[a-z\d]\.)*[a-z][-a-z\d]*[a-z]";
	    $portRegex = "(:\d{1,})?";
	    $pathRegex = "(\/[^?<>#\"\s]+)?";
	    $queryRegex = "(\?[^<>#\"\s]+)?";

	    $urlRegex = "/(?:(?<=^)|(?<=\s))((ht|f)tps?:\/\/" . $hostRegex . $portRegex . $pathRegex . $queryRegex . ")/";
	   	
	   	if($linkname){
	    	return preg_replace($urlRegex, "<a ".html_attributes($attributes)." href=\"\\1\">".$linkname."</a>", $str);
	    }else{
	    	return preg_replace($urlRegex, "<a ".html_attributes($attributes)." href=\"\\1\">\\1</a>", $str);
	    }
	    
	}
}


if (! function_exists('html_attributes')) {
	function html_attributes($array=[], $prefix=false, $excluded=[]) {
	
		if(!$array) return;

		$ret="";

		
		foreach ($array as $k => $v)
		{	
			//los data los pongo todos, los attributes solo los que tengan valor
			if(!in_array($k, $excluded) && ($prefix || $v) ){
				

				$ret.=" ".($prefix?($prefix."-"):"").$k."=";

				if(is_array($v) || is_object($v)){

					$ret.="'".json_encode($v)."' ";
				}else{
					$ret.="\"".addslashes($v)."\" ";
				}
			}

		}
	
		
		return $ret;

	}
	    
	
}