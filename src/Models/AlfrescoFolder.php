<?php
namespace Ajtarragona\AlfrescoLaravel\Models;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoObject;

class AlfrescoFolder extends AlfrescoObject{

	
	//private $cmisfolder;
	//private $provider;
	protected $description;	

	public function cmisfolder($newcmisfolder = null)
    {
         static $cmisfolder;
         if ($newcmisfolder !== null) {
             $cmisfolder = $newcmisfolder;
         }
         return $cmisfolder;
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
		
	}
	
	


	/**
	 * Converteix un objecte Folder de l'API REst d'Alfresco en un AlfrescoFolder
	 * @param folder
	 * @param provider
	 * @return
	 */
	public static function fromRestFolder($folder, $provider){
		$f = new self();
		$f->provider($provider);
		$f->type=$provider::TYPE_FOLDER;

		$f->id = $folder->id;
		$f->name = $folder->name;
		$f->description = isset($folder->properties->{'cm:description'})?$folder->properties->{'cm:description'}:'';
		$f->createdBy = $folder->createdByUser->id;
		$f->updatedBy = $folder->modifiedByUser->id;
		

		$f->parentId = $folder->parentId;
		
		$f->fullpath = $folder->path->name."/".$f->name;
		$f->path = $provider->getPath($f->fullpath);

		
		//$f->path = substr( $f->fullpath , strlen($provider->getBasepath(true)));
		
		$f->created =$folder->createdAt;
		$f->updated = $folder->modifiedAt;

		$f->downloadurl = $provider->getDownloadUrl($f); //($cmisdocument->getContentUrl());
		return $f;

	}
	
	/**
	 * Converteix un objecte Folder de l'API CMIS d'Alfresco en un AlfrescoFolder
	 * @param cmisfolder
	 * @param provider
	 * @return
	 */
	public static function fromCmisFolder($cmisfolder, $provider){
		//_dump($cmisfolder);
		$folder = new self();
		$folder->cmisfolder($cmisfolder);
		$folder->provider($provider);
		$folder->type=$provider::TYPE_FOLDER;
	

		$folder->id = $cmisfolder->prop("objectId");
		$folder->name = $cmisfolder->prop("name");
		$folder->description = $cmisfolder->prop("description");
		$folder->createdBy = $cmisfolder->prop("createdBy");
		$folder->updatedBy = $cmisfolder->prop("lastModifiedBy");
		

		$folder->fullpath = $cmisfolder->prop("path");
		$folder->path = $provider->getPath($cmisfolder->prop("path"));
		if($folder->path) $folder->parentId = $cmisfolder->prop("parentId");
		//else $folder->name ="ROOT FOLDER";
		$folder->created =$cmisfolder->prop("creationDate");
		
		//if(!$cmisfolder->isRootFolder())
		
		$folder->updated = $cmisfolder->prop("lastModificationDate");

		$folder->downloadurl = $provider->getDownloadUrl($folder); //($cmisdocument->getContentUrl());
		
		return $folder;
	}
	
	
	/**
	 * Retorna els fills d'una carpeta Alfresco
	 * @return
	 */
	public function getChildren($objectType=false,$page=1) {
		return $this->provider()->getChildren($this->id,$objectType,$page);
	}
	public function getFolders($page=1) {
		return $this->provider()->getFolders($this->id,$page);
	}
	public function getDocuments($page=1) {
		return $this->provider()->getDocuments($this->id,$page);
	}


	/**
	 * Retorna el fill d'una carpeta Alfresco amb un nom passat 
	 * @return
	 */
	public function getChild($name){
		$children = $this->provider()->getChildren($this->id);
		if($children){
			foreach($children as $child){
				if($child->name==$name) return $child;
			}
		}
		return false;
	}
	
	
	
	/**
	 * Crea una carpeta dins d'aquesta carpeta amb el nom passat 
	 * @param folderName
	 * @return
	 */
	public function createFolder($folderName){// throws AlfrescoObjectAlreadyExistsException{
		return $this->provider()->createFolder($folderName,$this->id);
	}

	/**
	 * Afegeix un document dins d'aquesta carpeta  
	 * @param document
	 * @return
	 */
	public function addDocument($document) {
		/*try {
			String tmpname=BigInteger.probablePrime(50, new Random()).toString(Character.MAX_RADIX);
	    	File tempFile = File.createTempFile(tmpname,"."+document.getExtension());
			FileUtils.copyInputStreamToFile(document.getInputStream(), tempFile);
			return this.provider.createDocument(document.getName(),tempFile, this.id);
		} catch (AlfrescoObjectNotFoundException  | AlfrescoObjectAlreadyExistsException | IOException e) {
			e.printStackTrace();
		}
		return null;*/
	}
	
	
	
	/**
	 * Esborra aquesta carpeta
	 */
	public function delete() {
		return $this->provider()->delete($this->id);
	}

	
	/**
	 * Renombra aquesta carpeta
	 * @param newName
	 * @return
	 */
	public function rename($newName){// throws AlfrescoObjectAlreadyExistsException{
		return $this->provider()->rename($this->id,$newName);
	}

	
	/**
	 * Copia aquesta carpeta dins la carpeta amb l'ID passat
	 * @param parentID
	 * @return
	 */
	public function copyTo($parentId){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider()->copy($this->id, $parentId);
	}
	
	
	/**
	 * Copia aquesta carpeta dins la carpeta amb el path passat
	 * @param parentPath
	 * @return
	 */
	public function copyToPath($parentPath){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider()->copyByPath($this->id, $parentPath);
	}

	/**
	 * Mou aquesta carpeta dins la carpeta amb l'ID passat
	 * @param parentID
	 * @return
	 */
	public function moveTo($parentId){//throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider()->move($this->id, $parentId);
	}

	/**
	 * Mou aquesta carpeta dins la carpeta amb el path passat
	 * @param parentPath
	 * @return
	 */
	public function moveToPath($parentPath){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider()->moveByPath($this->id, $parentPath);
	}
	

	
	

	/**
	 * Retorna el n�mero d'arxius que cont� aquesta carpeta. Opcionalment podem rec�rrer recursivament totes les carpetes filles (pot ser lent).
	 * @param recursive
	 * @return
	 */
	public function getFilesCount($recursive=false) {
		$ret=0;
		$children=$this->getChildren();
		if($children){
			foreach($children as $child){
			
				if($child->isDocument()){
					$ret++;
				}else{
					if($recursive){
						$ret+= $child->getFilesCount($recursive);
					}
				}
			}
		}
		
		return $ret;
	}


	/**
	 * Retorna la suma de la mida dels arxius que cont� aquesta carpeta. Opcionalment podem rec�rrer recursivament totes les carpetes filles (pot ser lent).
	 * @param recursive
	 * @return
	 */
	public function getFilesSize($recursive=false) {
		$ret=0;
		$children=$this->getChildren();
		if($children){
			foreach($children as $child){
			
				if($child->isDocument()){
					$ret+=$child->size;
				}else{
					if($recursive){
						$ret+= $child->getFilesSize($recursive);
					}
				}
			}
		}
		
		return $ret;
	}




	public function getParent() {
		if($this->parentId){
			return $this->provider()->getParent($this->id);
		}
	}
		
	
	

	

	public function search($query,$recursive=false){
		return $this->provider()->search($query,$this->id,$recursive);
	}

	
	

	
	

	
}
