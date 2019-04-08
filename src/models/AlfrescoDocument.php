<?php
namespace Ajtarragona\AlfrescoLaravel\Models;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoObject;
use Ajtarragona\AlfrescoLaravel\Models\Helpers\AlfrescoHelper;

class AlfrescoDocument extends AlfrescoObject {
	
	const OBJECT_TYPE = "cmis:document";
	public $extension;
	public $mimetype;
	public $mimetypedescription;
	
	public $size;
	public $humansize;

	public $version;
	public $author;
	public $title;


	public function cmisdocument($newcmisdocument = null)
    {
         static $cmisdocument;
         if ($newcmisdocument !== null) {
             $cmisdocument = $newcmisdocument;
         }
         return $cmisdocument;
    }

    public function provider($newprovider = null)
    {
         static $provider;
         if ($newprovider !== null) {
             $provider = $newprovider;
         }
         return $provider;
    }
	

	
	public function __construct() {
		$this->type=self::OBJECT_TYPE;
	}
	
	
	/**
	 * Converteix un objecte Document de l'API CMIS d'Alfresco en un AlfrescoDocument
	 * @param cmisdocument
	 * @param provider
	 * @return
	 */
	public static function fromCmisDocument($cmisdocument, $provider){
		//_dump($cmisdocument);
		$doc = new self();
		
		$doc->cmisdocument($cmisdocument);
		$doc->provider($provider);

		$doc->id = $cmisdocument->prop("objectId");
		$doc->name = $cmisdocument->prop("name");
		$doc->description = $cmisdocument->prop("description","cm");
		$doc->title = $cmisdocument->prop("title","cm");
		$doc->createdBy = $cmisdocument->prop("createdBy");
		$doc->updatedBy = $cmisdocument->prop("lastModifiedBy");
		$doc->version = $cmisdocument->prop("versionLabel");
		$doc->author = $cmisdocument->prop("author","cm");


		$doc->extension = AlfrescoHelper::getExtension($doc->name);
		$parent=$provider->getParent($doc->id);
		$parentpath=$parent->fullpath;

		//$path=str_replace($parentpath);
		//$doc->fullpath = $cmisfolder->prop("path");
		
		$doc->fullpath = $parentpath."/".$doc->name;//$cmisdocument->prop("path");
		$doc->path = $provider->getPath($doc->fullpath);

		//$doc->path = substr( $doc->path , strlen($provider->getBasepath()));
		$doc->parentId = $parent->id;
		
		$doc->created =$cmisdocument->prop("creationDate");
		$doc->updated = $cmisdocument->prop("lastModificationDate");

		//_dump($provider);

		$doc->downloadurl = $provider->getDownloadUrl($doc); //($cmisdocument->getContentUrl());
		$doc->viewurl = $provider->getViewUrl($doc); //($cmisdocument->getContentUrl());

		$doc->mimetype =  $cmisdocument->prop("contentStreamMimeType");
		$doc->mimetypedescription = AlfrescoHelper::getShortType($doc->mimetype);
		
		$doc->size = $cmisdocument->prop("contentStreamLength");
		$doc->humansize = AlfrescoHelper::humanFileSize($doc->size);

		
		return $doc;
	}
	
	
	/**
	 * Esborra aquest document
	 */
	public function delete() {
		return $this->provider()->delete($this->id);
	}

	
	
	/**
	 * Renombra aquest document
	 * @param newName
	 * @return
	 */
	public function rename($newName){// throws AlfrescoObjectAlreadyExistsException{
		return $this->provider()->rename($this->id,$newName);


	}

	/**
	 * Copia aquest document dins la carpeta amb l'ID passat
	 * @param parentID
	 * @return
	 */
	public function copyTo($parentId){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider()->copy($this->id, $parentId);
	}

	
	/**
	 * Copia aquest document dins la carpeta amb el path passat
	 * @param parentPath
	 * @return
	 */
	public function copyToPath($parentPath){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider()->copyByPath($this->id, $parentPath);
	}

	
	
	/**
	 * Mou aquest document dins la carpeta amb l'ID passat
	 * @param parentID
	 * @return
	 */
	public function moveTo($parentId){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider()->move($this->id, $parentId);
	}

	
	/**
	 * Mou aquest document dins la carpeta amb el path passat
	 * @param parentPath
	 * @return
	 */
	public function moveToPath($parentPath){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider()->moveByPath($this->id, $parentPath);
	}
	

	
	
	
	
	
	
	public function isFolder() {
		return $this->type != self::OBJECT_TYPE;
	}

	
	


	public function getParent(){
		if($this->parentId){
			return $this->provider()->getParent($this->id);
		}
	}
	

	/**
	 * Retorna el contingut de l'arxiu
	 */
	public function getContent() {
		return $this->provider()->getDocumentContent($this->id);
	}
	

}
