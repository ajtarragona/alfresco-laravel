<?php
namespace Ajtarragona\AlfrescoLaravel\Models\Helpers;
 

class AlfrescoObjectComparator {

	const SORT_DIR_ASC = "asc";
	const SORT_DIR_DESC = "desc";
	
	const SORT_FIELD_NAME = "NAME";
	const SORT_FIELD_SIZE = "SIZE";
	const SORT_FIELD_CREATED_DATE = "CREATED";
	const SORT_FIELD_UPDATED_DATE = "UPDATED";
	const SORT_FIELD_TYPE = "TYPE";
	const SORT_FIELD_CREATED_BY = "CREATEDBY";
	const SORT_FIELD_UPDATED_BY = "UPDATEDBY";


	public $sortdirection;
	public $foldersfirst;
	private $multiplier=1;


	public function __construct($sortdirection=false, $foldersfirst=true) {
		if(!$sortdirection) $sortdirection = self::SORT_DIR_ASC;
		else $this->sortdirection=$sortdirection;

		$this->foldersfirst=$foldersfirst;

		if($this->sortdirection == self::SORT_DIR_DESC ) $this->multiplier=-1;

			
	}

	public function sortAlphabetical($objects,$attribute){
		if(!$objects) return;
		usort($objects, function($o1, $o2) use ($attribute){

			$n1=removeAccents($o1->$attribute);
			$n2=removeAccents($o2->$attribute);
			
			$compare =strcasecmp($n1, $n2);

			if($this->foldersfirst){
				if($o1->isFolder() && $o2->isDocument()) $compare =-$this->multiplier;
				else if($o1->isDocument() && $o2->isFolder()) $compare = $this->multiplier;
			}

	        $ret= $this->multiplier * $compare;
	        // _dump("RETORNO:".$ret);
	        return $ret;
		});
		return $objects;
		
	}

	public function sortByName($objects){
		return $this->sortAlphabetical($objects,"name");
	}
	

	public function sortByCreator($objects){
		return $this->sortAlphabetical($objects,"createdBy");
	}

	
	public function sortByUpdater($objects){
		return $this->sortAlphabetical($objects,"updatedBy");	
	}
	


	public function sortBySize($objects){
		if(!$objects) return;
		usort($objects, function($o1, $o2){
			$n1=removeAccents($o1->name);
			$n2=removeAccents($o2->name);
			
			$s1=$o1->isDocument()?$o1->size:0;
			$s2=$o2->isDocument()?$o2->size:0;
			
			$compare = (($s1 - $s2)<0)?-1:1;

			if($this->foldersfirst){
				if($o1->isFolder() && $o2->isDocument()) $compare=-$this->multiplier;
				else if($o1->isFolder() && $o2->isFolder())  $compare= strcasecmp($n1, $n2);
				else if($o1->isDocument() && $o2->isFolder()) $compare=$this->multiplier;
				
			}else{
				if($o1->isDocument() && $o2->isDocument()){
					
				}else if($o1->isDocument()){
					$compare= -1;
				}else{
					$compare= 1;
				}
			}
	        $ret= $this->multiplier * $compare;

	        // _dump("RETORNO:".$ret);
	        return $ret;
		});
		return $objects;
		
	}



	public function sortByCreated($objects){
		if(!$objects) return;
		
		usort($objects, function($o1, $o2){
			
			$compare = ($o1->created > $o2->created)?1:-1;

			if($this->foldersfirst){
				if($o1->isFolder() && $o2->isDocument()) $compare=-$this->multiplier;
				else if($o1->isDocument() && $o2->isFolder()) $compare=$this->multiplier;
			}
			 
		
	        $ret= $this->multiplier * $compare;

	        // _dump("RETORNO:".$ret);
	        return $ret;
		});
		return $objects;
		
	}

	public function sortByUpdated($objects){
		if(!$objects) return;

		usort($objects, function($o1, $o2){
						
			$compare = ($o1->updated > $o2->updated)?1:-1;

			if($this->foldersfirst){
				if($o1->isFolder() && $o2->isDocument()) $compare=-$this->multiplier;
				else if($o1->isDocument() && $o2->isFolder()) $compare=$this->multiplier;
			}
			 
		
	        $ret= $this->multiplier * $compare;

	        // _dump("RETORNO:".$ret);
	        return $ret;
		});
		return $objects;
		
	}
	
	
	public function sortByType($objects){
		if(!$objects) return;

		usort($objects, function($o1, $o2){

			$m1=$o1->isDocument()?($o1->mimetype?$o1->mimetype:""):"";
			$m2=$o2->isDocument()?($o2->mimetype?$o2->mimetype:""):"";
			
			$compare =strcasecmp($m1, $m2);

			if($this->foldersfirst){
				if($o1->isFolder() && $o2->isDocument()) $compare =-$this->multiplier;
				else if($o1->isDocument() && $o2->isFolder()) $compare = $this->multiplier;
			}

	        $ret= $this->multiplier * $compare;
	        // _dump("RETORNO:".$ret);
	        return $ret;
		});
		return $objects;
		
	}

	
}