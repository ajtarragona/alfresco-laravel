<?php


if (! function_exists('removeAccents')) {

	function removeAccents($str){
		//$str=utf8_encode($str);
		//echo $str;
		if(class_exists('Transliterator')){
			$transliterator = Transliterator::createFromRules(':: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', Transliterator::FORWARD);
			return $transliterator->transliterate($str);
		}else{
			$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
								'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
								'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
								'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
								'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
			return strtr( $str, $unwanted_array );
			
		}
	}
}


if (! function_exists('str_replace_first')) {	
	function str_replace_first($search, $replace, $subject) {
	    $pos = strpos($subject, $search);
	    if ($pos !== false) {
	        return substr_replace($subject, $replace, $pos, strlen($search));
	    }
	    return $subject;
	}
}




if (! function_exists('strleft')) {	
	
	function strleft($s1, $s2) {
		 return substr($s1, 0, strpos($s1, $s2));
	}
	
   
}


