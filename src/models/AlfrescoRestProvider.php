<?php

namespace Ajtarragona\AlfrescoLaravel\Models;

use Illuminate\Http\UploadedFile;

use Ajtarragona\AlfrescoLaravel\Models\Vendor\Zip\TbsZip;

use Ajtarragona\AlfrescoLaravel\Models\AlfrescoDocument;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoFolder;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoConnectionException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoObjectNotFoundException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoObjectAlreadyExistsException;
use Ajtarragona\AlfrescoLaravel\Models\Helpers\AlfrescoHelper;

use Log;
use Exception;

class AlfrescoRestProvider
{

    const REPEATED_RENAME = "rename";
    const REPEATED_OVERWRITE = "overwrite";
    const REPEATED_DENY = "deny" ;    


	
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


	/**
	 * Realitza la connexió amb el repository Alfresco. Mètode d'ús intern
	 * throws AlfrescoConnectionException
	 * throws AlfrescoObjectNotFoundException
	 */
	private function connect(){ // throws AlfrescoConnectionException {
		try{
			$apiurl=$this->generateApiUrl();
			
			//TODO

			if($this->debug) Log::debug("ALFRESCO: Connecting to Rest API:" .$apiurl);
			
			

		}catch(Exception $e){
			Log::error("Error connecting to Alfresco server");
			Log::error($e->getMessage());
			throw new AlfrescoConnectionException(__("Error connecting to Alfresco server"));
		}
	}


	private function checkInBaseFolder($object){
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
	 * Genera la URL del servei Rest d'Alfresco
	 * @return
	 */
	private function generateApiUrl() {
		return $this->alfrescourl."api/".$this->repoId . "/public/alfresco/versions/". $this->apiversion;
	}
	


	
	/**
	 * Retorna el BaseFolder (el directori arrel a partir del basepath, si està definit)
	 * @return
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getBaseFolder(){ // throws AlfrescoObjectNotFoundException{
		try{
			
			//TODO

		}catch(Exception $e){
			throw new AlfrescoObjectNotFoundException(__("Folder [:name] not found in Alfresco", ["name"=>$this->basepath]) );
		}
	}


	
	/**
	 * Converteix un objecte de tipus json en un AlfrescoObject
	 * @param o
	 * @return
	 */
	protected function fromRestObject($o){
		
	}


	protected function fromRestObjects($objects){
		$ret=array();
		if($objects){
			foreach($objects as $object){
				$ret[]=$this->fromRestObject($object);
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
			$curl = curl_init();
            
			curl_setopt_array($curl, array(
                CURLOPT_URL => $this->generateApiUrl().'/nodes/'.$objectId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => $this->apiuser.':'.$this->apipwd
            ));

			$content = curl_exec($curl);
			//dump(json_decode($content));
            $info = curl_getinfo($curl);
            //dump($info);

            if($info['http_code'] == 200){
                return $content;
            } else {
                return false;
            }
			//TODO
			
			
		}catch(Exception $e){
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
			
			//TODO
			
			
		}catch(Exception $e){
			throw new AlfrescoObjectNotFoundException(__("Object Path [:name] not found in Alfresco",["name"=>$objectPath]));
		}
	}



	private function scandirRecursive($descendants){
		
		$objects=[];//TODO
		
		$ret=array();
		foreach($objects as $obj){
			$okobj=$this->fromRestObject($obj);
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
			$descendants=[]; //TODO
			
			if($descendants){
				//_dump($descendants);
				$archives=$this->scandirRecursive($descendants);

				$zip = new TbsZip(); // instantiate the class
				$zip->CreateNew(); // create a virtual new zip archive

				if($archives){
					foreach($archives as $archive){
						$archivepath=str_replace($obj->path."/","",$archive->path);
						//_dump($archivepath);
						$content= "";//TODO
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
			//TODO

			if($ret->isFolder()){
				return $ret;
			}else{
				throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco",array("name"=>$folderId)));
			}
			
		}catch(Exception $e){
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
			
		}catch(Exception $e){
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
			//TODO

		}catch(Exception $e){
			throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco",array("name"=>$folderId)));
		}
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
			//TODO


			
		}catch(Exception $e){
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
	private function doCreateFolder($folderName, $parentfolder){ // throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{

		try{
			
			//TODO

		}catch(Exception $e){
			
			
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
			//TODO

		}catch(Exception $e){
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
			
		}catch(Exception $e){
			throw new AlfrescoObjectNotFoundException(__("Document path [:name] not found in Alfresco",array("name"=>$documentPath)));
		}

		
	}


	public function getDocumentContent($documentId){
		try{
			//TODO
					
			
			
			
		}catch(Exception $e){
			throw new AlfrescoObjectNotFoundException(__("Document ID [:name] not found in Alfresco", array("name"=>$documentId)));
		}
	}

	
	

	
	
	/**
	 * Elimina el document o carpeta d'Alfresco amb l'ID passat
	 * @param objectId
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function delete($objectId){// throws AlfrescoObjectNotFoundException {
		//TODO

	}

	
	
	
	/**
	 * Copia el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb l'ID passat
	 * @param objectId
	 * @param folderId
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function copy($objectId, $folderId){// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException {
		//TODO
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
		
		//TODO
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
		
		//TODO
	}
	
	
	private function doCreateDocument($parentId, $filename, $filecontent, $filetype=false, $index=0){

		//_dump($filename);
		//TODO
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
			$ret=$this->doUpload($parentId,$doc);
		}
		
		return false;
	
	}

	
	
	/**
	 * Retorna todos los Sites de alfresco (como objetos AlfrescoFolder)
	 * @return
	 */
	public function getSites(){
		
		//TODO

	}



	
	public function getPath($path){
		return str_replace_first( $this->getBasepath(true), "", $path );
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
		//TODO

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
    	
	


}