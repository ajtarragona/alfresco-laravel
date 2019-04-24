<?php
namespace Ajtarragona\AlfrescoLaravel\Models;
use Ajtarragona\AlfrescoLaravel\Models\Helpers\AlfrescoHelper;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoCmisProvider;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoRestProvider;

abstract class AlfrescoObject {
	
	public $id;
	public $name;
	public $path;
	public $fullpath;
	public $type;
	public $parentId;
	public $created;
	public $updated;
	public $downloadurl;
	
	public $description;
	
	public $createdBy;
	public $updatedBy;

	
	
	public abstract function delete();
	public abstract function rename($newName);// throws AlfrescoObjectAlreadyExistsException;
	public abstract function copyTo($parentId);// throws AlfrescoObjectNotFoundException, RepositoryObjectAlreadyExistsException;
	public abstract function copyToPath($parentPath);// throws AlfrescoObjectNotFoundException, RepositoryObjectAlreadyExistsException;
	
	public abstract function moveTo($parentId);// throws AlfrescoObjectNotFoundException, RepositoryObjectAlreadyExistsException;
	public abstract function moveToPath($parentPath);// throws AlfrescoObjectNotFoundException, RepositoryObjectAlreadyExistsException;
	
	public function isFolder() {
		return $this->type == AlfrescoCmisProvider::TYPE_FOLDER || $this->type == AlfrescoRestProvider::TYPE_FOLDER;
	}
	
	public function isDocument(){ 
		return $this->isFile();
	}
	public function isFile(){
		return !$this->isFolder();
	}

	public function __toString()
    {
        return json_encode($this);
    }
	public function isBaseFolder(){
		return $this->isFolder() && ($this->path=="" || $this->path==$this->fullpath);
	}

	public function getBreadcrumb(){
		$ret=[];
		if(!$this->isBaseFolder()){
			$crumbs=explode("/",$this->path);
			
			while(count($crumbs)>0){
				//dump($name);
				$ret[]=[
					"path"=>implode("/",$crumbs),
					"name"=>array_last($crumbs)
				];
				array_pop($crumbs);
			}
		}
		return array_reverse($ret);
	}

	public function getIcon(){
		if($this->isFile()) return AlfrescoHelper::getIcon($this->mimetype);
		else return "folder";
	}

	public function getColor(){
		if($this->isFile()) return AlfrescoHelper::getColor($this->mimetype);
		else return "info";
	}

	public function renderIcon(){
		return icon($this->getIcon(),['class'=>'mr-2','color'=>$this->getColor(),'size'=>'lg']);
			
	}

	public function isImage(){
		return ($this->isFile() && AlfrescoHelper::isImage($this->mimetype));
	}

	public function isPdf(){
		return ($this->isFile() && AlfrescoHelper::isPdf($this->mimetype));
	}
	public function hasPreview(){
		return $this->isFile();// && AlfrescoHelper::hasPreview($this->mimetype));
	}

	
	
	
	
	
}
