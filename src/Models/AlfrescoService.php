<?php

namespace Ajtarragona\AlfrescoLaravel\Models;

use Ajtarragona\AlfrescoLaravel\Models\AlfrescoCmisProvider;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoRestProvider;


class AlfrescoService
{

    const REPEATED_RENAME = "rename";
    const REPEATED_OVERWRITE = "overwrite";
    const REPEATED_DENY = "deny" ;    


	protected $provider;


	public function __construct($settings=false) { 

		if(!$settings) $settings=config('alfresco');
		$settings=to_object($settings);
		
		if($settings->api=="cmis") $this->provider = new AlfrescoCmisProvider($settings);
		else $this->provider = new AlfrescoRestProvider($settings);
		
		//$this->connect();
	}


	
	
	/**
	 * Retorna el directori arrel des del qual s'executaran els altres mètodes 
	 * @return String
	 */
	public function getBasepath($full=false){
		return $this->provider->getBasepath($full);
	}
	

	/**
	 * Defineix el directori arrel des del qual s'executaran els altres mètodes.
	 * @param basepath
	 */
	public function setBasepath($path){
		$this->provider->setBasepath($path);
	}


	
	/**
	 * Retorna el BaseFolder (el directori arrel a partir del basepath, si està definit)
	 * @return
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getBaseFolder(){ // throws AlfrescoObjectNotFoundException{
		
		return $this->provider->getBaseFolder();
	}


	
	
	public function exists($objectId){
		return $this->provider->exists($objectId);
	}


    public function existsPath($objectPath){
		return $this->provider->existsPath($objectPath);
    }

	
	


	/**
	 * Retorna un objecte d'Alfresco passant el seu ID
	 * @param objecteId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getObject($objectId){
		return $this->provider->getObject($objectId);
	}


	/**
	 * Retorna un objecte d'Alfresco passant el seu ID
	 * @param objecteId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getObjectByPath($objectPath){
		return $this->provider->getObjectByPath($objectPath);
	}



	/**
	 * Descarrega el contingut d'un objecte passant el seu ID
	 * @param objecteId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function downloadObject($objectId, $stream=false){
		$this->provider->downloadObject($objectId, $stream);		
	}



	/**
	 * Retorna una carpeta d'Alfresco passant el seu ID
	 * @param folderId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getFolder($folderId){// throws AlfrescoObjectNotFoundException {
		return $this->provider->getFolder($folderId);		
	}

	
	
	/**
	 * Retorna una carpeta d'Alfresco passant la seva ruta (a partir del basepath)
	 * @param folderPath
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getFolderByPath($folderPath){// throws AlfrescoObjectNotFoundException {
		return $this->provider->getFolderByPath($folderPath);	
	}
	
	
	/**
	 * Retorna la carpeta pare de l'objecte amb l'ID passat
	 * @param objectId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	 public function getParent($objectId){// throws AlfrescoObjectNotFoundException {
		return $this->provider->getParent($objectId);
	}


	/**
	 * Retorna els fills d'una carpeta d'Alfresco passant el seu ID
	 * @param folderId
	 * @return AlfrescoFolder[]
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getChildren($folderId, $objectType=false,$page=1){
		return $this->provider->getChildren($folderId, $objectType,$page);
	}

	
		

	/**
	 * Crea una carpeta passant el seu nom dins la carpeta amb l'ID passat
	 * Retorna la carpeta creada
	 * @param folderName
	 * @param parentId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function createFolder($folderName, $parentId=false){
		return $this->provider->createFolder($folderName, $parentId);
	}


	
	
	/**
	 * Retorna un document d'Alfresco passant el seu ID 
	 * @param documentId
	 * @return AlfrescoDocument
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getDocument($documentId){//	throws AlfrescoObjectNotFoundException {
		return $this->provider->getDocument($documentId);
	}

	
	
	
	/**
	 * Retorna un document d'Alfresco passant la seva ruta (a partir del basepath)
	 * @param documentId
	 * @return AlfrescoDocument
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getDocumentByPath($documentPath){// throws AlfrescoObjectNotFoundException {
		return $this->provider->getDocumentByPath($documentPath);
	}


	public function getDocumentContent($documentId){
		return $this->provider->getDocumentContent($documentId);
	}

	
	/**
	 * Elimina el document o carpeta d'Alfresco amb l'ID passat
	 * @param objectId
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function delete($objectId){// throws AlfrescoObjectNotFoundException {
		return $this->provider->delete($objectId);
	}

	
	
	
	/**
	 * Copia el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb l'ID passat
	 * @param objectId
	 * @param folderId
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function copy($objectId, $folderId){// throws AlfrescoObjectNotFoundException, 
		return $this->provider->copy($objectId, $folderId);
	}
	
	

	/**
	 * Copia el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb la ruta passada (a partir del basepath)
	 * @param objectId
	 * @param folderPath
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function copyByPath($objectId, $folderPath){
		return $this->provider->copyByPath($objectId, $folderPath);
	}

	
	
	
	

	/**
	 * Mou el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb l'ID passat
	 * @param objectId
	 * @param folderId
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function move($objectId, $folderId){// throws AlfrescoObjectNotFoundException, 
		return $this->provider->move($objectId, $folderId);
	}
	
	
	

	/**
	 * Mou el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb la ruta passada (a partir del basepath)
	 * @param objectId
	 * @param folderPath
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function moveByPath($objectId, $folderPath){
		return $this->provider->moveByPath($objectId, $folderPath);
	}

	
	
	
	
	/**
	 * Renombra el document o carpeta d'Alfresco amb l'ID passat amb un nou nom
	 * @param objectId
	 * @param newName
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function rename($objectId, $newName){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		return $this->provider->rename($objectId, $newName);
	}
	
	
	
	/**
	 * Crea un nou document a Alfresco a partir d'un objecte File a la carpeta amb l'ID passat
	 * @param filename
	 * @param file
	 * @param parentId
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function createDocument($parentId, $filename, $filecontent, $filetype=false){
		return $this->provider->createDocument($parentId, $filename, $filecontent, $filetype);
		
	}

	
	/**
	 * Crea un nou document a Alfresco a partir d'un objecte File dins la carpeta amb la ruta passada (a partir del basepath)
	 * @param filename
	 * @param file
	 * @param parentPath
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function createDocumentByPath($parentPath, $filename, $filecontent, $filetype=false){
		return $this->provider->createDocumentByPath($parentPath, $filename, $filecontent, $filetype);
	}


	

	public function upload($parentId, $documents){
		return $this->provider->upload($parentId, $documents);
	}

	
	
	/**
	 * Retorna todos los Sites de alfresco (como objetos AlfrescoFolder)
	 * @return
	 */
	public function getSites(){
		return $this->provider->getSites();
	}



		

	/**
	 * Busca documents que continguin el text passat al nom o al contingut a partir de la carpeta amb l'ID passat
	 * @param query
	 * @param folderId
	 * @param recursive
	 * @return ArrayList<AlfrescoObject>
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function search($query, $folderId=false, $recursive=false){// throws AlfrescoObjectNotFoundException {
		return $this->provider->search($query, $folderId, $recursive);
	}
	
	
	
	
	
	
	/**
	 * Busca documents que continguin el text passat al nom o al contingut a partir de la carpeta amb la ruta passada (a partir de la carpeta arrel o al basepath si està definit)
	 * @param query
	 * @param folderPath
	 * @param recursive
	 * @return ArrayList<AlfrescoObject>
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function searchByPath($query, $folderPath=false, $recursive=false){//	throws AlfrescoObjectNotFoundException {
		return $this->provider->searchByPath($query, $folderPath, $recursive);

	}
	

	
	
	public function getDownloadUrl($object){
		return $this->provider->getDownloadUrl($object);
    }
    
    /*return the user download url of a file */
    public function getViewUrl($object){
		return $this->provider->getViewUrl($object);
    }


    public function getRepeatedPolicy() {
		return $this->provider->getRepeatedPolicy();
    }


    public function setRepeatedPolicy($repeatedPolicy) {
		$this->provider->setRepeatedPolicy($repeatedPolicy);
    }
    
    public function setRepeatedRename() {
		$this->provider->setRepeatedRename();
    }

    public function setRepeatedOverwrite() {
		$this->provider->setRepeatedOverwrite();
    }

    public function setRepeatedDeny() {
		$this->provider->setRepeatedDeny();
    }


    public function isRepeatedRename() {
		return $this->provider->isRepeatedRename();
    }
    

    public function isRepeatedOverwrite() {
		return $this->provider->isRepeatedOverwrite();
    }
    
    public function isRepeatedDeny() {
		return $this->provider->isRepeatedDeny();
    }
    	
	


	public function getPreview($id, $type="pdf"){
		return $this->provider->getPreview($id, $type);
	}


}