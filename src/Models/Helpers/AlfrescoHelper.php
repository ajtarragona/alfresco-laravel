<?php

namespace Ajtarragona\AlfrescoLaravel\Models\Helpers;

use Ajtarragona\AlfrescoLaravel\Models\Helpers\AlfrescoObjectComparator;


class AlfrescoHelper{


	public static function getIcon($mimetype){
		$icons=array(
			"application/msword"=>"file-word",
			"application/vnd.openxmlformats-officedocument.wordprocessingml.document"=>"file-word",
			"application/vnd.oasis.opendocument.text"=>"file-word",
			"application/vnd.ms-excel"=>"file-excel",
			"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"=>"file-excel",
			"application/vnd.oasis.opendocument.spreadsheet"=>"file-excel",
			"application/vnd.ms-powerpoint"=>"file-powerpoint",
			"application/vnd.openxmlformats-officedocument.presentationml.presentation"=>"file-powerpoint",
			"application/vnd.oasis.opendocument.presentation"=>"file-powerpoint",
			"application/pdf"=>"file-pdf",
			"text/plain"=>"file-alt",
			"image/jpeg"=>"file-image",
			"image/png"=>"file-image",
			"image/gif"=>"file-image",
			"application/xml"=>"file-code",
			"application/zip"=>"file-archive",
			"video/mp4"=>"file-video",
			"audio/x-wav"=>"file-video",
			"audio/mpeg"=>"file-audio",
			"video/quicktime"=>"file-audio"
		);
		if(isset($icons[$mimetype])) return $icons[$mimetype];
		else return "file";
	}





	public static function hasPreview($mimetype){
		$valid=[
			'text/plain',
			'image/jpeg',
			"image/png",
			"image/gif",
			"application/pdf"
		];

		return in_array($mimetype, $valid);
	}

	public static function isImage($mimetype){
		$valid=[
			'image/jpeg',
			"image/png",
			"image/gif"
		];

		return in_array($mimetype, $valid);
	}

	public static function isPdf($mimetype){
		$valid=[
			'application/pdf'
		];

		return in_array($mimetype, $valid);
	}

	public static function getColor($mimetype){
		$colors=array(
			"application/msword"=>"#3E6DB5",
			"application/vnd.openxmlformats-officedocument.wordprocessingml.document"=>"#3E6DB5",
			"application/vnd.oasis.opendocument.text"=>"#3E6DB5",
			"application/vnd.ms-excel"=>"#28be4b",
			"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"=>"#28be4b",
			"application/vnd.oasis.opendocument.spreadsheet"=>"#28be4b",
			"application/vnd.ms-powerpoint"=>"#B83B1D",
			"application/vnd.openxmlformats-officedocument.presentationml.presentation"=>"#B83B1D",
			"application/vnd.oasis.opendocument.presentation"=>"#B83B1D",
			"application/pdf"=>"#db3232",
			"text/plain"=>"#666666",
			"image/jpeg"=>"#7e40db",
			"image/png"=>"#7e40db",
			"image/gif"=>"#7e40db",
			"application/xml"=>"#666666",
			"application/zip"=>"#e6d210",
			"video/mp4"=>"#dd38a9",
			"audio/x-wav"=>"#dd38a9",
			"audio/mpeg"=>"#dd38a9",
			"video/quicktime"=>"#dd38a9"
		);
		if(isset($colors[$mimetype])) return $colors[$mimetype];
		else return "#333333";
	}




	public static function getShortType($mimetype){
		$noms=array(
			"application/msword"=>__("Document"),
			"application/vnd.openxmlformats-officedocument.wordprocessingml.document"=>__("Document"),
			"application/vnd.oasis.opendocument.text"=>__("Document"),
			"application/vnd.ms-excel"=>__("Full de càlcul"),
			"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"=>__("Full de càlcul"),
			"application/vnd.oasis.opendocument.spreadsheet"=>__("Full de càlcul"),
			"application/vnd.ms-powerpoint"=>__("Presentació"),
			"application/vnd.openxmlformats-officedocument.presentationml.presentation"=>__("Presentació"),
			"application/vnd.oasis.opendocument.presentation"=>__("Presentació"),
			"application/pdf"=>__("Document PDF"),
			"text/plain"=>__("Text plà"),
			"image/jpeg"=>__("Imatge"),
			"image/png"=>__("Imatge"),
			"image/gif"=>__("Imatge"),
			"application/xml"=>__("Codi"),
			"application/zip"=>__("Arxiu comprimit"),
			"video/mp4"=>__("Audio"),
			"audio/x-wav"=>__("Audio"),
			"audio/mpeg"=>__("Video"),
			"video/quicktime"=>__("Video")
		);
		if(isset($noms[$mimetype])) return $noms[$mimetype];
		else return __("Tipus desconegut");
	}





	public static function sanitizeName($name)
    {
    	//return $name;

    	return preg_replace('/[^ a-zA-Z0-9-_\.]/', '',removeAccents($name));

    	//return snake_case($name);
    	/*$output = htmlspecialchars($name, 0, "UTF-8");
		if ($output == "") {
		    $output = htmlspecialchars(utf8_encode($name), 0, "UTF-8");
		}
		return $output;*/

       //return $name; // return strtolower(preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $name));
    }
	



	public static function sanitizeDir($name)
    {
    	return preg_replace('/[^ a-zA-Z0-9-_\.\/]/', '',removeAccents( $name));

       //return $name; // return strtolower(preg_replace('/[^a-zA-Z0-9-_\.\/]/', '_', $name));
    }
	




	public static function checkname($name){
		if($name=="") return "";
		$dirs=explode("/",$name);
		if($dirs){
			foreach($dirs as $dir){
				if($dir==".." || $dir=="." || $dir=="-"){
					header("HTTP/1.0 400 Bad Request");
					return false;
				}
			}
		}
		return end($dirs);
	}
	
	




	public static function generateNewName($filename, $index){
		$path_parts = pathinfo($filename);
		$basename=$path_parts['filename'];
		$extension=$path_parts['extension'];

		if($index>0){
			$basename= substr($basename, 0, strrpos($basename,"_"));
		}
		
		$index++;
		
		return $basename."_".$index.".".$extension;
	}
	
	




	public static function humanFileSize($size,$unit="") {
	  if( (!$unit && $size >= 1<<30) || $unit == "GB")
		return number_format($size/(1<<30),2)."GB";
	  if( (!$unit && $size >= 1<<20) || $unit == "MB")
		return number_format($size/(1<<20),2)."MB";
	  if( (!$unit && $size >= 1<<10) || $unit == "KB")
		return number_format($size/(1<<10),2)."KB";
	  return number_format($size)." bytes";
	}





	public static function copyfolder($source, $dest, $permissions = 0755) 
	{ 

	        // Check for symlinks
			if (is_link($source)) {
				return symlink(readlink($source), $dest);
			}

			// Simple copy for a file
			if (is_file($source)) {
				return copy($source, $dest);
			}

			// Make destination directory
			if (!is_dir($dest)) {
				mkdir($dest, $permissions);
			}

			// Loop through the folder
			$dir = dir($source);
			while (false !== $entry = $dir->read()) {
				if ($entry == '.' || $entry == '..') {
					continue;
				}
				// Deep copy directories
				self::copyfolder("$source/$entry", "$dest/$entry", $permissions);
			}

			$dir->close();
			return true;

	} 	

	




	public static function getExtension($name){
		$path_parts = pathinfo($name);
		return isset($path_parts['extension'])?$path_parts['extension']:'';
		
	}








	public static function sortByName($objects,$sortdirection="ASC", $foldersfirst=true){
		return self::sort($objects, AlfrescoObjectComparator::SORT_FIELD_NAME, $sortdirection, $foldersfirst);
	}




	public static function sortByUpdated($objects,$sortdirection="ASC", $foldersfirst=true){
		return self::sort($objects, AlfrescoObjectComparator::SORT_FIELD_UPDATED_DATE, $sortdirection, $foldersfirst);
	}




	public static function sortByCreated($objects,$sortdirection="ASC", $foldersfirst=true){
		return self::sort($objects, AlfrescoObjectComparator::SORT_FIELD_CREATED_DATE, $sortdirection, $foldersfirst);
	}


	public static function sortByCreator($objects,$sortdirection="ASC", $foldersfirst=true){
		return self::sort($objects, AlfrescoObjectComparator::SORT_FIELD_CREATED_BY, $sortdirection, $foldersfirst);
	}
	

	public static function sortByUpdater($objects,$sortdirection="ASC", $foldersfirst=true){
		return self::sort($objects, AlfrescoObjectComparator::SORT_FIELD_UPDATED_BY, $sortdirection, $foldersfirst);
	}





	public static function sortBySize($objects,$sortdirection="ASC", $foldersfirst=true){
		return self::sort($objects, AlfrescoObjectComparator::SORT_FIELD_SIZE, $sortdirection, $foldersfirst);
	}





	public static function sortByType($objects,$sortdirection="ASC", $foldersfirst=true){
		return self::sort($objects, AlfrescoObjectComparator::SORT_FIELD_TYPE, $sortdirection, $foldersfirst);
	}







	public static function sort($objects,$sortfield="NAME", $sortdirection="ASC", $foldersfirst=true){
		$comparator=new AlfrescoObjectComparator($sortdirection, $foldersfirst);
			 	
		switch($sortfield){
			case AlfrescoObjectComparator::SORT_FIELD_UPDATED_DATE : 
			 	$objects=$comparator->sortByUpdated($objects);
				break;
			case AlfrescoObjectComparator::SORT_FIELD_CREATED_DATE : 
			 	$objects=$comparator->sortByCreated($objects);
				break;
			case AlfrescoObjectComparator::SORT_FIELD_CREATED_BY : 
			 	$objects=$comparator->sortByCreator($objects);
				break;
			case AlfrescoObjectComparator::SORT_FIELD_UPDATED_BY : 
			 	$objects=$comparator->sortByUpdater($objects);
				break;
			case AlfrescoObjectComparator::SORT_FIELD_TYPE : 
			 	$objects=$comparator->sortByType($objects);
				break;
			case AlfrescoObjectComparator::SORT_FIELD_SIZE : 
			 	$objects=$comparator->sortBySize($objects);
				break;
			case AlfrescoObjectComparator::SORT_FIELD_NAME :  
			default: 
			 	$objects=$comparator->sortByName($objects);
			 	break;
		}

		return $objects;

	}

	public static function download($contents, $name, $mime, $size, $stream=false){
		header("Pragma: public");
		header("Expires: -1");
		header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
		
		if (!$stream){
			header("Content-Disposition: attachment; filename=\"".$name."\"");
		}else{
			header("Content-Disposition: inline; filename=\"".$name."\"");
		}
		
		header("Content-Type: " . $mime);
		header("Content-Length: ".$size);
		
		print $contents;
		ob_flush();
		flush();
		exit;
	}

}