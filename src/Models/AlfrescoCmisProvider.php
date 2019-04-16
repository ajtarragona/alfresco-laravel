<?php

namespace Ajtarragona\AlfrescoLaravel\Models;

use Illuminate\Http\UploadedFile;

use Ajtarragona\AlfrescoLaravel\Models\Vendor\Zip\TbsZip;

use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\CMISService;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisInvalidArgumentException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisObjectNotFoundException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisPermissionDeniedException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisNotSupportedException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisConstraintException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisRuntimeException;
use Ajtarragona\AlfrescoLaravel\Models\Vendor\Cmis\Exceptions\CmisNotImplementedException;

use Ajtarragona\AlfrescoLaravel\Models\AlfrescoCmisObject;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoDocument;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoFolder;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoConnectionException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoObjectNotFoundException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoObjectAlreadyExistsException;
use Ajtarragona\AlfrescoLaravel\Models\Helpers\AlfrescoHelper;

use Log;
use Exception;

class AlfrescoCmisProvider
{

    const REPEATED_RENAME = "rename";
    const REPEATED_OVERWRITE = "overwrite";
    const REPEATED_DENY = "deny" ;    

    const TYPE_FOLDER = "cmis:folder";
    const TYPE_DOCUMENT = "cmis:document";

	
	protected $rootpath;
	protected $basepath;
	protected $alfrescourl;
	protected $apiuser;
	protected $apipwd;
	protected $apiversion;
	protected $repoId;
	protected $repeatedPolicy;
	protected $reponame;
	protected $session;
	protected $debug;


	

	public function __construct($settings=false) { 

		if(!$settings){
			$settings=config('alfresco');
			$settings=to_object($settings);
		}
		
		$this->rootpath=$settings->base_path;
		if(!ends_with($this->rootpath,"/")) $this->rootpath.="/";
		
		

		$this->basepath= "";

		$this->alfrescourl = $settings->url;
		if(!ends_with($this->alfrescourl,"/")) $this->alfrescourl.="/";

		$this->apiuser = $settings->user;
		$this->apipwd = $settings->pass;
		
		$this->api= $settings->api;
		$this->apiversion = $settings->api_version;
		
		$this->repoId = $settings->repository_id;
		
		$this->repeatedPolicy = $settings->repeated_policy;
		$this->debug = $settings->debug;

		
		$this->connect();
	}
	public function getRootPath(){
		return $this->rootpath;
	}

	/**
	 * Realitza la connexió amb el repository Alfresco. Mètode d'ús intern
	 * throws AlfrescoConnectionException
	 * throws AlfrescoObjectNotFoundException
	 */
	private function connect(){ // throws AlfrescoConnectionException {
		try{
			$apiurl=$this->generateApiUrl();
			
			if($this->debug) Log::debug("ALFRESCO: Connecting to CMIS API:" .$apiurl);
			
			$this->session = new CMISService($apiurl, $this->apiuser, $this->apipwd);

			$ret=$this->session->getObjectByPath($this->getBasepath(true));
			
			if(!$ret){
				Log::error("Alfresco basepath not found");
				throw new AlfrescoObjectNotFoundException(__("Alfresco basepath not found"));
			}

		}catch(CmisRuntimeException | CmisObjectNotFoundException $e){
			Log::error("Error connecting to Alfresco server");
			Log::error($e->getMessage());
			throw new AlfrescoConnectionException(__("Error connecting to Alfresco server"));
		}
	}


	private function checkInBaseFolder($object){
		
		//_dump($object->path);
		//_dump($this->getBasepath(true));
		if(!starts_with($object->path, $this->getBasepath(true))){ 
			return true;
		}else {
			throw new AlfrescoObjectNotFoundException(__("Object :name doesn't belong to the current site",["name"=>$object->id]));
		}
		

		
	}
	
	
	
/**
	 * Retorna el directori arrel des del qual s'executaran els altres mètodes 
	 * @return String
	 */
	public function getBasepath($full=false){
		return ($full?($this->rootpath):"") . $this->basepath;
	}
	

	/**
	 * Defineix el directori arrel des del qual s'executaran els altres mètodes.
	 * @param basepath
	 */
	public function setBasepath($path){
		$this->basepath=$path;
		if(!ends_with($this->basepath,"/")) $this->basepath.="/";
	}


	/**
	 * Genera la URL del servei CMIS d'Alfresco
	 * @return
	 */
	private function generateApiUrl() {
		return $this->alfrescourl."api/".$this->repoId . "/public/cmis/versions/". $this->apiversion. "/atom";
	}
	


	
	/**
	 * Retorna el BaseFolder (el directori arrel a partir del basepath, si està definit)
	 * @return
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getBaseFolder(){ // throws AlfrescoObjectNotFoundException{
		
		try{
			if(!$this->basepath)
				$obj=$this->session->getObjectByPath($this->rootpath);
			else 
				$obj=$this->session->getObjectByPath($this->getBasepath(true));

			return $this->fromCmisObject($obj); 
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder [:name] not found in Alfresco", ["name"=>$this->basepath]) );
		}
	}


	
	/**
	 * Converteix un objecte de tipus AlfrescoCmisObject en un AlfrescoObject
	 * @param o
	 * @return
	 */
	protected function fromCmisObject($o){
		//dump($o);
		$obj=new AlfrescoCmisObject($o);

		//dd($obj->type);
		//System.out.println("["+ o.getName() + "] which is of type: " + type.getId()+"-"+type.getDisplayName());
		if($obj->type == self::TYPE_DOCUMENT){

			return AlfrescoDocument::fromCmisDocument($obj, $this) ;
		}else if($obj->type == self::TYPE_FOLDER || $obj->type == "F:st:sites"  || $obj->type =="F:st:site" ){
			//dd($obj);
			return AlfrescoFolder::fromCmisFolder($obj, $this);
		}else return null;
	}


	protected function fromCmisObjects($objects){
		$ret=array();
		if($objects){
			foreach($objects as $object){
				$ret[]=$this->fromCmisObject($object);
			}
		}
		return $ret;

	}


	
	
	
	public function exists($objectId){
		try{
			$this->getObject($objectId);
			return true;
		}catch(AlfrescoObjectNotFoundException $e){
			return false;
		}	
	}


    public function existsPath($objectPath){
		try{
			$this->getObjectByPath($objectPath);
			return true;
		}catch(AlfrescoObjectNotFoundException $e){
			return false;
		}
    }

	
	


	/**
	 * Retorna un objecte d'Alfresco passant el seu ID
	 * @param objecteId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getObject($objectId){

		try{
			$tmp= $this->session->getObject($objectId);
			$ret=$this->fromCmisObject($tmp);
			
			$this->checkInBaseFolder($ret);

			return $ret;
			
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Object ID [:name] not found in Alfresco",["name"=>$objectId]));
		}
	}


	/**
	 * Retorna un objecte d'Alfresco passant el seu ID
	 * @param objecteId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getObjectByPath($objectPath){
		try{
			$objectPath=ltrim($objectPath, '/');
			//dd($objectPath);
		

			$thepath=$this->getBasepath(true).$objectPath;
			$thepath=urlencode($thepath);
			//dump($thepath);
			if($thepath == ""){
				$cmisobject = $this->session->getObjectByPath($this->rootpath);
			}else{
				$cmisobject=$this->session->getObjectByPath($thepath);
				//dd($cmisobject);
			}
			//_dump($cmisfolder);

			$ret=$this->fromCmisObject($cmisobject);
			return $ret;
			
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Object Path [:name] not found in Alfresco",["name"=>$objectPath]));
		}
	}



	private function scandirRecursive($descendants){
		
		$objects=$descendants->objectList;
		
		$ret=array();
		foreach($objects as $obj){
			$okobj=$this->fromCmisObject($obj);
			if($okobj->isFolder()){
		
				if(isset($obj->children)){
					$ret=array_merge($ret,$this->scandirRecursive($obj->children));
				}
			}else{
				$ret[]=$okobj;
			}
		}
		return $ret;
	}


	/**
	 * Descarrega el contingut d'un objecte passant el seu ID
	 * @param objecteId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function downloadObject($objectId, $stream=false){
		$obj=$this->getObject($objectId);
		$is_attachment = !$stream;
		
		if($obj->isDocument()){
			header("Pragma: public");
			header("Expires: -1");
			header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
			
			if ($is_attachment){
				header("Content-Disposition: attachment; filename=\"".$obj->name."\"");
			}else{
				header("Content-Disposition: inline; filename=\"".$obj->name."\"");
			}
			
			header("Content-Type: " . $obj->mimetype);
			header("Content-Length: ".$obj->size);
			
			$doc= $this->getDocumentContent($objectId);
			print $doc;
			ob_flush();
			flush();
			exit;

		}else{
			$descendants=$this->session->getDescendants($obj->id,-1);
			
			if($descendants){
				//$descendants= $this->fromCmisObjects($descendants->objectList);
				//_dump($descendants);
				$archives=$this->scandirRecursive($descendants);

				$zip = new TbsZip(); // instantiate the class
				$zip->CreateNew(); // create a virtual new zip archive

				if($archives){
					foreach($archives as $archive){
						$archivepath=str_replace($obj->path."/","",$archive->path);
						//_dump($archivepath);
						$content=$this->session->getContentStream($archive->id);
						$zip->FileAdd($archivepath, $content, TbsZip::TBSZIP_STRING);
					}
					// flush the result as an HTTP download
				}
				$zip->Flush(TbsZip::TBSZIP_DOWNLOAD, $obj->name.".zip");
				exit;
			}

		}
	}



	/**
	 * Retorna una carpeta d'Alfresco passant el seu ID
	 * @param folderId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getFolder($folderId){// throws AlfrescoObjectNotFoundException {
		//RepositoryLog.debug("ALFRESCO: getFolder("+folderId+")");
		try{
			$tmp= $this->session->getObject($folderId);
			//dd($tmp);
			//die();
			$ret=$this->fromCmisObject($tmp);
			
			$this->checkInBaseFolder($ret);

			if($ret->isFolder()){
				return $ret;
			}else{
				throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco",array("name"=>$folderId)));
			}
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco",array("name"=>$folderId)));
		}
	}

	
	
	/**
	 * Retorna una carpeta d'Alfresco passant la seva ruta (a partir del basepath)
	 * @param folderPath
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getFolderByPath($folderPath){// throws AlfrescoObjectNotFoundException {
		try{
			if($folderPath=="") return $this->getBaseFolder();
			
			$ret=$this->getObjectByPath($folderPath);
			if($ret->isFolder()){
				return $ret;
			}else{
				throw new AlfrescoObjectNotFoundException(__("Folder path [:name] not found in Alfresco",array("name"=>$folderPath)));
			}
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder path [:name] not found in Alfresco",array("name"=>$folderPath)));
		}

	}
	
	
	/**
	 * Retorna la carpeta pare de l'objecte amb l'ID passat
	 * @param objectId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	 public function getParent($objectId){// throws AlfrescoObjectNotFoundException {
		try{
			//_dump("getParent");
			//_dump($objectId);
			$parent=$this->session->getFolderParent($objectId);
			//_dump($parent);
			if($parent) return $this->fromCmisObject($parent);

		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco",array("name"=>$folderId)));
		}
	}


	public function getFolders($folderId){
		return $this->getChildren($folderId, self::TYPE_FOLDER);
	}
	public function getDocuments($folderId){
		return $this->getChildren($folderId, self::TYPE_DOCUMENT);
	}
	/**
	 * Retorna els fills d'una carpeta d'Alfresco passant el seu ID
	 * @param folderId
	 * @return AlfrescoFolder[]
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getChildren($folderId, $objectType=false){
		// throws AlfrescoObjectNotFoundException {
		
		try{
			$children=$this->session->getChildren($folderId);
			//dump($children);
			$ret=array();
			if($children){
				foreach($children->objectList as $obj){
					
					$child = $this->fromCmisObject($obj);
					
					if($child){
						if($objectType){
							if($objectType==$child->type) $ret[]=$child;
						}else{
							$ret[]=$child;
						}
					}
				}
			}
			return $ret;
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco",array("name"=>$folderId)));
		}
	}

	
		
	/**
	 * Mètode intern que realitza l'acció de crear la carpeta
	 * @param folderName
	 * @param parentfolder
	 * @return
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	private function doCreateFolder($folderName, $parentfolder){
		// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{

		//$folderName=AlfrescoHelper::sanitizeName($folderName);

		try{
			//dd("ALFRESCO: createFolder(".$folderName.") in folder " . $parentfolder->id);
			
    		if(str_contains($folderName,"/")){
    			//si tiene subdirectorios

    			$path=explode("/", $folderName);
    			$partpath="";
    			foreach($path as $part){
    				$partpath.="/".$part;
    				//dump($partpath);
					try{
    					$parentfolder=$this->getFolderByPath($partpath);
    				}catch(AlfrescoObjectNotFoundException $e){
    					//dd($e);
    					$parentfolder=$this->doCreateFolder($part, $parentfolder);
    				}
    			}
    			return $parentfolder;
    		}else{
				$ret=$this->session->createFolder($parentfolder->id, $folderName);

				return $this->fromCmisObject($ret);
			}
			
		}catch(CmisRuntimeException | CmisInvalidArgumentException | CmisRuntimeException $e ){
			//return $this->doCreateFolder(AlfrescoHelper::sanitizeName($folderName), $parentfolder);
			//dd(AlfrescoHelper::sanitizeName($folderName));
			$ret=$this->session->createFolder($parentfolder->id, AlfrescoHelper::sanitizeName($folderName));
			return $this->fromCmisObject($ret);

		}catch(CmisConstraintException | CmisContentAlreadyExistsException $e){
			throw new AlfrescoObjectAlreadyExistsException(__("Folder ':name' already exists in folder ':path' in Alfresco ",array("name"=>$folderName, "path"=>$parentfolder->path)));
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder path ':name' not found in Alfresco",array("name"=>$parentfolder->path)));
		}

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
	// throws AlfrescoObjectNotFoundException,AlfrescoObjectAlreadyExistsException {
		if(!$parentId){
			$parentFolder = $this->getBaseFolder();
		}else{
			$parentFolder = $this->getFolder($parentId);
		}

		if($parentFolder)
			return $this->doCreateFolder($folderName,$parentFolder);

	}


	
	
	/**
	 * Retorna un document d'Alfresco passant el seu ID 
	 * @param documentId
	 * @return AlfrescoDocument
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getDocument($documentId){//	throws AlfrescoObjectNotFoundException {
		try{
			$tmp= $this->session->getObject($documentId);
			$ret=$this->fromCmisObject($tmp);
			if($ret->isDocument()){
				return $ret;
			}else{
				throw new AlfrescoObjectNotFoundException(__("Document ID [:name] not found in Alfresco",array("name"=>$documentId)));
			}
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Document ID [:name] not found in Alfresco", array("name"=>$documentId)));
		}
	}

	
	
	
	/**
	 * Retorna un document d'Alfresco passant la seva ruta (a partir del basepath)
	 * @param documentId
	 * @return AlfrescoDocument
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getDocumentByPath($documentPath){// throws AlfrescoObjectNotFoundException {
		
		try{
			$ret=$this->getObjectByPath($documentPath);
			
			if($ret->isDocument()){
				return $ret;
			}else{
				throw new AlfrescoObjectNotFoundException(__("Document path [:name] not found in Alfresco",array("name"=>$documentPath)));
			}
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Document path [:name] not found in Alfresco",array("name"=>$documentPath)));
		}

		
	}


	public function getDocumentContent($documentId){
		try{
			$tmp= $this->session->getObject($documentId);
					
			$ret=$this->fromCmisObject($tmp);

			if($ret->isDocument()){
				$contents=$this->session->getContentStream($documentId);
				return $contents;
			}else{
				throw new AlfrescoObjectNotFoundException(__("Document ID [:name] not found in Alfresco",array("name"=>$documentId)));
			}
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Document ID [:name] not found in Alfresco", array("name"=>$documentId)));
		}
	}

	
	/**
	 * Mètode internq ue executa l'acció de cerca sobre Alfresco
	 * @param qs
	 * @return
	 */
	private function manageDocumentQuery($qs) {
		/*RepositoryLog.debug(qs.toString());
		
		ArrayList<RepositoryDocument> ret= new ArrayList<RepositoryDocument>();
		
		ItemIterable<QueryResult> results = qs.query();
		
		for(QueryResult queryResult: results) {  
			
			String idDocument = queryResult.getPropertyByQueryName("cmis:objectId").getFirstValue().toString();
			Document cmisdocument = (Document) session.getObject(idDocument);
	        ret.add(AlfrescoDocument.fromCmisDocument(cmisdocument,this));
		}
		
		return ret;*/
		
	}
	
	
	
	
	/**
	 * Elimina el document o carpeta d'Alfresco amb l'ID passat
	 * @param objectId
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function delete($objectId){// throws AlfrescoObjectNotFoundException {
		$cmisobject= $this->session->getObject($objectId);
		$obj=$this->fromCmisObject($cmisobject);
		try{

			if($obj->isDocument()){
				//dd($obj->id);
				$ret=$this->session->deleteObject($obj->id);
				return $ret==false;
			}else{
				$ret=$this->session->deleteTree($obj->id);
				return $ret->numItems==-1;
			}
		}catch(CmisPermissionDeniedException |CmisRuntimeException $e){
			dd($e);
			return false;
		}

	}

	
	
	
	/**
	 * Copia el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb l'ID passat
	 * @param objectId
	 * @param folderId
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function copy($objectId, $folderId){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException {
		try{
			$cmisobject= $this->session->getObject($objectId);
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Object ID [:name] not found in Alfresco", array("name"=>$objectId)));
		}

		try{
			$cmisfolder=$this->session->getObject($folderId);
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco", array("name"=>$folderId)));
		}
			
		
		$obj=$this->fromCmisObject($cmisobject);
		
		$target=$this->fromCmisObject($cmisfolder);


		if($target->isFolder()){

			//_dump("Copying :".$obj->path ." to folder ".$target->path);
			
			if($obj->isDocument()){
				$contents=$this->session->getContentStream($obj->id);
				
				$properties=$this->session->getPropertiesOfLatestVersion($obj->id);

				try{
					$ret=$this->session->createDocument($target->id, $obj->name, $properties, $contents, trim($obj->mimetype));
					return $this->fromCmisObject($ret);

				}catch(Exception $e){
					throw new AlfrescoObjectAlreadyExistsException(__("Object with name ':name' already exists in folder :path in Alfresco", array("name"=>$obj->name,"path"=>$target->fullpath)));
				}
			}else{
				//create folder and copy contents
				try{
					$parent=$this->createFolder($obj->name,$target->id);

				}catch(Exception $e){
					throw new AlfrescoObjectAlreadyExistsException(__("Object with name ':name' already exists in folder :path in Alfresco", array("name"=>$obj->name, "path"=>$target->fullpath)));
				}

				
				$children=$this->getChildren($obj->id);
				//_dump($children);
				//die();
				if($children){
					foreach($children as $child){
						$this->copy($child->id,$parent->id);
					}
				}
				return $parent;

			}
			
		}else{
			throw new AlfrescoObjectNotFoundException(__("Target ID [:name] is not a folder", array("name"=>$folderId)));
		}
			
		
	}
	
	

	/**
	 * Copia el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb la ruta passada (a partir del basepath)
	 * @param objectId
	 * @param folderPath
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function copyByPath($objectId, $folderPath){
		$folder=$this->getFolderByPath($folderPath);

		if($folder){
			return $this->copy($objectId,$folder->id);
		}
	}

	
	
	
	

	/**
	 * Mou el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb l'ID passat
	 * @param objectId
	 * @param folderId
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function move($objectId, $folderId){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException {
		try{
			$cmisobject= $this->session->getObject($objectId);
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Object ID [:name] not found in Alfresco", array("name"=>$objectId)));
		}

		try{
			$cmisfolder=$this->session->getObject($folderId);
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco", array("name"=>$folderId)));
		}

		$obj=$this->fromCmisObject($cmisobject);
		
		$target=$this->fromCmisObject($cmisfolder);


		//_dump($obj);

		if($target->isFolder()){
			$origin=$obj->getParent();

			//_dump("Moving :".$obj->path ." to folder ".$target->path);
			//_dump($origin);

			try{
				$ret=$this->session->moveObject($obj->id, $target->id, $origin->id);
				return $this->fromCmisObject($ret);
			}catch(Exception $e){
				throw new AlfrescoObjectAlreadyExistsException(__("Object with name ':name' already exists in folder :path in Alfresco", array("name"=>$obj->name,"path"=>$target->path)));
			}
		}

		return false;
	}
	
	
	

	/**
	 * Mou el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb la ruta passada (a partir del basepath)
	 * @param objectId
	 * @param folderPath
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function moveByPath($objectId, $folderPath){
		$folder=$this->getFolderByPath($folderPath);
		if($folder){
			return $this->move($objectId,$folder->id);
		}
	}

	
	
	
	
	/**
	 * Renombra el document o carpeta d'Alfresco amb l'ID passat amb un nou nom
	 * @param objectId
	 * @param newName
	 * @throws AlfrescoObjectNotFoundException
	 * @throws AlfrescoObjectAlreadyExistsException
	 */
	public function rename($objectId, $newName){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{
		
		$newName=AlfrescoHelper::sanitizeName($newName);

		try{
			$cmisobject= $this->session->getObject($objectId); 
			$obj=$this->fromCmisObject($cmisobject);
			
			if($obj->name==$newName) return $obj;

			$target=$obj->getParent();
			
			if($obj->isDocument()){
				if(!ends_with($newName,".".$obj->extension)) $newName.=".".$obj->extension;

				$contents=$this->session->getContentStream($obj->id);
				
				$properties=$this->session->getPropertiesOfLatestVersion($obj->id);
				//_dump("Renaming from:".$obj->name ." to ". $newName );
				
				try{
					$ret=$this->session->createDocument($target->id, $newName, $properties, $contents, trim($obj->mimetype));
					$this->delete($objectId);
					return $this->fromCmisObject($ret);
				}catch(Exception $e){
					throw new AlfrescoObjectAlreadyExistsException(__("Object with name ':name' already exists in folder :path in Alfresco", array("name"=>$obj->name,"path"=>$target->fullpath)));
				}
				
				
			}else{
				//Create folder and move contents
				try{
					$parent=$this->createFolder($newName,$target->id);
				}catch(Exception $e){
					throw new AlfrescoObjectAlreadyExistsException(__("Object with name ':name' already exists in folder :path in Alfresco", array("name"=>$newName, "path"=>$target->fullpath)));
				}
				$children=$this->getChildren($obj->id);
				if($children){
					foreach($children as $child){
						$child->moveTo($parent->id);
					}
				}
				//remove original folder
				$this->delete($obj->id);
				return $parent;

			}
			
			
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Object ID ':name' not found in Alfresco",array("name"=>$objectId)));
		}catch(CmisContentAlreadyExistsException $e){
			throw new AlfrescoObjectAlreadyExistsException(__("Object with name ':name' already exists in the same folder in Alfresco",array("name"=>$newName)));
		}
	}
	
	
	private function doCreateDocument($parentId, $filename, $filecontent, $filetype=false, $index=0){

		//_dump($filename);
		$filename=AlfrescoHelper::sanitizeName($filename);
		//_dump($filename);
		//die();

		try{
			$cmisfolder=$this->session->getObject($parentId);
		
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco", array("name"=>$parentId)));
		}

		$folder=$this->fromCmisObject($cmisfolder);

		if($folder->isFolder()){
			try{
				
				
				//_dump("creating file '".$filename."' in folder ".$folder->id);
				
				$file=$this->session->createDocument($folder->id, $filename, false, $filecontent, trim($filetype));
				//_dump($file);
				$obj=$this->fromCmisObject($file);
				
				return $obj;
			}catch(Exception $e){
				//si ya existe y la politica es rename
				if($this->isRepeatedRename()){
					//_dump($filename);
					$newname=AlfrescoHelper::generateNewName($filename,$index);
					return $this->doCreateDocument($parentId, $newname, $filecontent, $filetype, ($index+1));

				}else if($this->isRepeatedOverwrite()){
					//delete original
					$obj=$this->getObjectByPath($folder->path."/".$filename);
					$obj->delete();
					$this->doCreateDocument($parentId, $filename, $filecontent, $filetype);

				}else{
					throw new AlfrescoObjectAlreadyExistsException(__("Object with name ':name' already exists in folder :path in Alfresco", array("name"=>$filename,"path"=>$folder->path)));

				}
			}


		}else{
			throw new AlfrescoObjectNotFoundException(__("Folder [:name] doesn't exist in Alfresco",array("name"=>$folder->id)));
		}
		return false;
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
		return $this->doCreateDocument($parentId, $filename, $filecontent, $filetype);
		
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
		$folder=$this->getFolderByPath($parentPath);
		if($folder){
			return $this->doCreateDocument($folder->id, $filename, $filecontent, $filetype);
		}
	}


	protected function doUpload($parentId, $document){
		$error=false;
		if($document instanceof UploadedFile){
			$filename=$document->getClientOriginalName();

			if(!$error=$document->getError()){
				$filecontent=file_get_contents($document->getRealPath());
				$filetype=$document->getMimeType();
			}
		}else{
			$filename=$doc["name"];
			$filecontent=file_get_contents($doc["tmp_name"]);
			$filetype=mime_content_type($doc["tmp_name"]);
		}

		//dump($error);
		if(!$error){
			try{
				$obj=$this->createDocument($parentId,$filename,$filecontent,$filetype);

				if($obj){
					return $obj;
				}else{
					 return __("L'arxiu <strong>:name</strong> ja existeix al repositori",["name"=>$filename]);
				}
			}catch(Exception $e){
				return __("Error pujant arxiu <strong>:name</strong> al repositori",["name"=>$filename]);
			}
		}else{
			return __("Error pujant arxiu <strong>:name</strong> al repositori",["name"=>$filename]);
		}
	}


	public function upload($parentId, $documents){
		
		$ret=array();

		if(is_array($documents)){
			foreach($documents as $doc){
				$ret[]=$this->doUpload($parentId,$doc);
			}
			return $ret;
		}else{
			return $this->doUpload($parentId,$documents);
		}
		
		return false;
	
	}

	
	
	/**
	 * Retorna todos los Sites de alfresco (como objetos AlfrescoFolder)
	 * @return
	 */
	public function getSites(){
		
		$sitefolder=$this->session->getObjectByPath("/Sites");
		$sitefolder=$this->fromCmisObject($sitefolder);

		return $sitefolder->getChildren();

	}



	
	public function getPath($path){

		return ltrim(substr( $path , strlen($this->rootpath)),"/");
		//str_replace_first( $this->getBasepath(true), "", $path );
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
		if(!$folderId){
			$base=$this->getBaseFolder();
			$folderId=$base->id;
		}
		$query = str_replace("'", "''", $query);

		$parent=$this->getFolder($folderId);

		$q="SELECT * FROM cmis:document WHERE (cmis:name LIKE '%s' or CONTAINS('%s')) AND ".($recursive?"IN_TREE":"IN_FOLDER") ."('%s') ";

		$statement=sprintf($q, "%".$query."%" , $query, $folderId);
		//_dump($statement);
		
		$ret=false;
		$results=$this->session->query($statement);
		
		if($results && isset($results->objectList) && !empty($results->objectList)){
			$ret=array();
			foreach($results->objectList as $obj){
				//_dump($obj);
				$okobj=$this->fromCmisObject($obj);
				//_dump($okobj);
				$ret[]=$okobj;
			}
		}
		//_dump($ret);
		return $ret;

		//return manageDocumentQuery(qs);*/
		
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
		$folder=$this->getFolderByPath($folderPath);
		if($folder){
			return $this->search($query, $folder->id,$recursive);
		}
	}
	


	public function getDownloadUrl($object){
		return route('alfresco.download',[$object->id]);
    }
    
    /*return the user download url of a file */
    public function getViewUrl($object){
        return route('alfresco.view',[$object->id]);
		
    }

    /*return the user preview url of a file */
    public function getPreviewUrl($object){
        return route('alfresco.preview',[$object->id]);
		
    }



    public function getRepeatedPolicy() {
        return $this->repeatedPolicy;
    }


    public function setRepeatedPolicy($repeatedPolicy) {
        if($repeatedPolicy!=self::REPEATED_DENY && $repeatedPolicy!=self::REPEATED_RENAME && $repeatedPolicy!=self::REPEATED_OVERWRITE) 
            $this->repeatedPolicy = self::REPEATED_DENY;
        else 
            $this->repeatedPolicy = $repeatedPolicy;
    }
    
    public function setRepeatedRename() {
        $this->repeatedPolicy = self::REPEATED_RENAME;
    }
    public function setRepeatedOverwrite() {
        $this->repeatedPolicy = self::REPEATED_OVERWRITE;
    }
    public function setRepeatedDeny() {
        $this->repeatedPolicy = self::REPEATED_DENY;
    }


    public function isRepeatedRename() {
        return $this->repeatedPolicy == self::REPEATED_RENAME;
    }
    

    public function isRepeatedOverwrite() {
        return $this->repeatedPolicy == self::REPEATED_OVERWRITE;
    }
    
    public function isRepeatedDeny() {
        $this->repeatedPolicy == self::REPEATED_DENY;
    }
    	
    public function getPreview($id){
    	return false;
    }
	


}