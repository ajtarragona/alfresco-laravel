<?php
namespace Ajtarragona\AlfrescoLaravel\Models;

class AlfrescoCmisObject{
	
	public $type;
	private $prefix="cmis:";
	private $cmisobject;

	public function __construct($cmisobject) {
		$this->cmisobject=$cmisobject;
		$this->type= $this->prop("objectTypeId");

	}

	public function props(){
		return $this->cmisobject->properties;
	}
	public function prop($name, $prefix=false){
		if(!$this->cmisobject) return "";
		if(!isset($this->cmisobject->properties)) return "";
		$prefix=$prefix?($prefix.":"):$this->prefix;

		
		if($name=="nodeRef") $prefix="alfcmis:";

		if(!is_array($this->cmisobject->properties) && !isset($this->cmisobject->properties[$prefix.$name])) return "";

		if(isset($this->cmisobject->properties[$prefix.$name])) return trim($this->cmisobject->properties[$prefix.$name]);
		return "";
	}

	
	public function __toString()
    {
        return json_encode($this);
    }


	
}