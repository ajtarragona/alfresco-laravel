<?php

namespace Ajtarragona\AlfrescoLaravel\Models;

use Illuminate\Http\UploadedFile;

use Ajtarragona\AlfrescoLaravel\Models\Vendor\Zip\TbsZip;

use Ajtarragona\AlfrescoLaravel\Models\AlfrescoDocument;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoFolder;

use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoConnectionException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoObjectNotFoundException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoObjectAlreadyExistsException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoAuthenticationException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoIntegrityException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoNotAllowedException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoSizeLimitException;

use Ajtarragona\AlfrescoLaravel\Models\Helpers\AlfrescoHelper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Log;
use Exception;

class AlfrescoRestProvider
{

    const REPEATED_RENAME = "rename";
    const REPEATED_OVERWRITE = "overwrite";
    const REPEATED_DENY = "deny" ;    
    
    const PAGINATION_ITEMS = 100;    


    const TYPE_FOLDER = "folder";
    const TYPE_DOCUMENT = "document";

    //TODO view pagination


	
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
	protected $verify_ssl;


	

	public function __construct($settings=false) { 

		if(!$settings){
			$settings=config('alfresco');
			$settings=to_object($settings);
		}
		
		// $this->rootpath=$settings->base_path;
		// if(!ends_with($this->rootpath,"/")) $this->rootpath.="/";
		
		$this->baseid=$settings->base_id;

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
		$this->verify_ssl = $settings->verify_ssl;

		
		$this->connect();
		$this->rootpath =  $this->getBasepath(true);
	}


	/**
	 * Realitza la connexió amb el repository Alfresco. Mètode d'ús intern
	 */
	private function connect(){ 
		$apiurl=$this->apiUrl();
		//dd($apiurl);
		if($this->debug) Log::debug("ALFRESCO: Connecting to Rest API:" .$apiurl);

		$this->client = new Client([
			'base_uri' => $apiurl,
			'verify' => $this->verify_ssl
		]);
	}




	protected function call($method, $url, $args=[]){
		$url=ltrim($url,"/");
		if(!$url) return false;

		if(!isset($args['auth'])) $args['auth'] = [$this->apiuser, $this->apipwd];

		//$args['query'] = array_merge(['include'=>'path'], isset($args["query"])?$args["query"]:[]);

		
		if($this->debug){
			Log::debug("ALFRESCO: Calling $method to url:" .$this->apiUrl($url));
			Log::debug("ALFRESCO: Options:");
			Log::debug($args);
		}
		
		// if($method=="PUT"){
		// 	dump("calling $method:".$this->apiUrl($url));
		// 	dump($args);
		// } 
		$ret=false;

		try{
			$response = $this->client->request($method, $url, $args);
			// dump($response->getStatusCode());
			//dump($response);
			//dd((string)$response->getBody());

			switch($response->getStatusCode()){
				case 200:
				case 201:
				case 204:
					//ok
					$ret = (string) $response->getBody();
					//dd($ret);
					if(isJson($ret)){
						$ret=json_decode($ret);
						//dump($ret);
					}else if(!$ret){
						$ret=true;
					}
					break;
				default: break;
			}

			return $ret;
		} catch (RequestException | ConnectException | ClientException $e) {
		    //dd($e->getMessage());
		    
		    if ($e->hasResponse()) {
		    	//dd($e->getResponse());
		       $status=$e->getResponse()->getStatusCode();

		       switch($status){
			       	case 404:
						throw new AlfrescoObjectNotFoundException(__("Object not found in Alfresco")); break;
					case 401:
						//Authentication exception
						throw new AlfrescoAuthenticationException(__("User authentication exception")); break;
					case 403:
						//Permissions exception
						throw new AlfrescoNotAllowedException(__("User doesn't have permission")); break;
					case 409: 
						//New name clashes with an existing node in the current parent folder
						throw new AlfrescoObjectAlreadyExistsException(__("Name already exists")); break;
					case 413: 
					case 507: 
						//size limit
						throw new AlfrescoSizeLimitException(__("Size limit exceeded")); break;
					case 422: 
						//name containing invalid characters
						throw new AlfrescoIntegrityException(__("Integrity Exception")); break;
					default: break;
					
		       }
		    }

		    Log::error("Error connecting to Alfresco server");
			Log::error($e->getMessage());
			// dd($e);
			throw new AlfrescoConnectionException(__("Error connecting to Alfresco server"));
		}
		
    }


	// private function checkInBaseFolder($object){
	// 	if(!starts_with($object->path, $this->getBasepath(true))){ 
	// 		return true;
	// 	}else {
	// 		throw new AlfrescoObjectNotFoundException(__("Object :name doesn't belong to the current site",["name"=>$object->id]));
	// 	}
		
	// }
	
	
	public function getRootPath(){
		return $this->rootpath;
	}
	
/**
	 * Retorna el directori arrel des del qual s'executaran els altres mètodes 
	 * @return String
	 */
	public function getBasepath($full=false){
		if($full){
			$folder=$this->getBaseFolder();
			return $folder->fullpath.($this->basepath?"/".$this->basepath:'');
		}else{
			return $this->basepath;
		}
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
	private function apiUrl($path=false) {
		$ret=$this->alfrescourl."api/".$this->repoId . "/public/alfresco/versions/". $this->apiversion."/";
		if($path && ltrim($path,"/")) $ret.=ltrim($path,"/");
		return $ret;
	}
	


	
	/**
	 * Retorna el BaseFolder (el directori arrel a partir del basepath, si està definit)
	 * @return
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getBaseFolder(){ // throws AlfrescoObjectNotFoundException{
		return $this->getObject($this->baseid);

	}


	
	/**
	 * Converteix un objecte de tipus json en un AlfrescoObject
	 * @param o
	 * @return
	 */
	protected function fromRestObject($o){
		//dump($o);
		if($o->entry->isFile){
			return AlfrescoDocument::fromRestDocument($o->entry, $this);
		}else if($o->entry->isFolder){
			return AlfrescoFolder::fromRestFolder($o->entry, $this);
		}else return null;

	}


	protected function fromRestObjects($entries){
		$ret=[];
		if($entries && $entries->list && $entries->list->pagination &&  $entries->list->pagination->count>0){
			foreach($entries->list->entries as $entry){
				$ret[]=$this->fromRestObject($entry);
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

		

		$response=$this->call('GET','nodes/'.$objectId,[
			"query"=>['include'=>'path']
		]);

		$obj=$this->fromRestObject($response);
		return $obj;
		
	}


	/**
	 * Retorna un objecte d'Alfresco passant el seu ID
	 * @param objecteId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getObjectByPath($objectPath){
		
		//dump($objectPath);
		$response=$this->call('GET','nodes/'.$this->baseid,[
			"query"=>['include'=>'path','relativePath'=>$objectPath]
		]);
		//dump($response);
		$obj=$this->fromRestObject($response);
		return $obj;
			
		
	}



	


	/**
	 * Descarrega el contingut d'un objecte passant el seu ID
	 * @param objecteId
	 * @return AlfrescoFolder
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function downloadObject($objectId, $stream=false){
		$obj=$this->getObject($objectId);
		
		if($obj->isDocument()){
			$contents= $this->getDocumentContent($objectId);
			AlfrescoHelper::download($contents, $obj->name, $obj->mimetype, $obj->size, $stream);
		}else{

			
			//_dump($descendants);
			$archives=$this->childrenRecursive($obj);
			$zip = new TbsZip(); // instantiate the class
			$zip->CreateNew(); // create a virtual new zip archive

			if($archives){
				foreach($archives as $archive){
					$archivepath=str_replace($obj->path."/","",$archive->path);
					//_dump($archivepath);
					$content= $this->getDocumentContent($archive->id);
					$zip->FileAdd($archivepath, $content, TbsZip::TBSZIP_STRING);
				}
				// flush the result as an HTTP download
			}
			$zip->Flush(TbsZip::TBSZIP_DOWNLOAD, $obj->name.".zip");
			exit;
			

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
		
		$ret=$this->getObject($folderId);

		if($ret->isFolder()){
			return $ret;
		}else{
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
		
		if($folderPath=="") return $this->getBaseFolder();
		
		$ret=$this->getObjectByPath($folderPath);
		if($ret->isFolder()){
			return $ret;
		}else{
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
		
		$obj=$this->getObject($objectId);
		if($obj){
			return $this->getObject($obj->parentId);
		}else{
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
	public function getChildren($folderId, $objectType=false, $page=1){
		// throws AlfrescoObjectNotFoundException {
		
		
			
		$params=[
			'include'=>'path',
			'maxItems' => self::PAGINATION_ITEMS,
			'skipCount' => ($page-1)*self::PAGINATION_ITEMS
		];
		
		if($objectType){
			if($objectType=="folder") $params["where"]="(isFolder=true)";
			if($objectType=="file"||$objectType=="document") $params["where"]="(isFile=true)";
		}



		$entries=$this->call('GET','nodes/'.$folderId.'/children',[
			"query"=>$params
		]);

		return $this->fromRestObjects($entries);

		

			
		
	}


	private function childrenRecursive($obj){
		
		$children=$this->getChildren($obj->id);

		if($children){
			$ret=array();
			foreach($children as $child){
				if($child->isFolder()){
					$subchild=$this->childrenRecursive($child);
					if($subchild){
						$ret=array_merge($ret,$subchild);
					}
				}else{
					$ret[]=$child;
				}
			}
			return $ret;
		}return false;
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
		//dump("doCreateFolder");

			if(str_contains($folderName,"/")){
				$path_parts = pathinfo($folderName);
				$relativePath = AlfrescoHelper::sanitizeDir($path_parts['dirname']);
				$folderName = AlfrescoHelper::sanitizeName($path_parts['basename']);
			}else{
				$folderName=AlfrescoHelper::sanitizeName($folderName);
			}

			$params=[
				"name"=>$folderName, 
				"nodeType"=>"cm:folder"
			];
			
			if(isset($relativePath)) $params["relativePath"]=$relativePath;


			
			$return=$this->call('POST','nodes/'.$parentfolder->id.'/children', [
				'query' => ['include'=>'path'],
				"json"=>$params
			]);

			$folder=$this->fromRestObject($return);
			return $folder;
			
		

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
		
		$ret=$this->getObject($documentId);
		if($ret->isDocument()){
			return $ret;
		}else{
			throw new AlfrescoObjectNotFoundException(__("Document ID [:name] not found in Alfresco",array("name"=>$documentId)));
		
		}

		
	}

	
	
	
	/**
	 * Retorna un document d'Alfresco passant la seva ruta (a partir del basepath)
	 * @param documentId
	 * @return AlfrescoDocument
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function getDocumentByPath($documentPath){// throws AlfrescoObjectNotFoundException {
		
		
		$ret=$this->getObjectByPath($documentPath);
		
		if($ret->isDocument()){
			return $ret;
		}else{
			throw new AlfrescoObjectNotFoundException(__("Document path [:name] not found in Alfresco",array("name"=>$documentPath)));
		}
			
		

		
	}


	public function getDocumentContent($documentId){
		
			$response=$this->call('GET','nodes/'.$documentId.'/content');
			return $response;
			
		
	}

	
	

	
	
	/**
	 * Elimina el document o carpeta d'Alfresco amb l'ID passat
	 * @param objectId
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function delete($objectId){// throws AlfrescoObjectNotFoundException {
		$object= $this->getObject($objectId);

		if($object){
			$response=$this->call('DELETE','nodes/'.$objectId);
			return true;
		}else{
			throw new AlfrescoObjectNotFoundException(__("Document path [:name] not found in Alfresco",array("name"=>$documentPath)));
		}
		
		

		
	}

	
	
	
	/**
	 * Copia el document o carpeta d'Alfresco amb l'ID passat dins de la carpeta amb l'ID passat
	 * @param objectId
	 * @param folderId
	 * @throws AlfrescoObjectNotFoundException
	 */
	public function copy($objectId, $folderId){
		// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException {
		$object= $this->getObject($objectId);

		if($object){
			$response=$this->call('POST','nodes/'.$objectId.'/copy',[
				"query"=>["include"=>'path'],
				'json'=>['targetParentId'=>$folderId]
			]);
			$obj=$this->fromRestObject($response);
			return $obj;
		}else{
			throw new AlfrescoObjectNotFoundException(__("Object [:id] not found in Alfresco",array("id"=>$objectId)));
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
	public function move($objectId, $folderId){
		// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException {
		
		$object= $this->getObject($objectId);

		if($object){
			$response=$this->call('POST','nodes/'.$objectId.'/move',[
				"query"=>["include"=>'path'],
				'json'=>['targetParentId'=>$folderId]
			]);
			$obj=$this->fromRestObject($response);
			return $obj;
		}else{
			throw new AlfrescoObjectNotFoundException(__("Object [:id] not found in Alfresco",array("id"=>$objectId)));
		}
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
		$obj=$this->getObject($objectId);

		$newName=AlfrescoHelper::sanitizeName($newName);

		if($obj->isDocument() && $obj->extension){
			if(!ends_with($newName,".".$obj->extension)) $newName.=".".$obj->extension;
		}
		
		$return=$this->call('PUT','nodes/'.$obj->id, [
			"query"=>["include"=>'path'],
			"json"=>["name"=>$newName]
		]);

		$obj=$this->fromRestObject($return);

		return $obj;
	}
	



	private function doCreateDocument($parentId, $filename, $filecontent, $filetype=false, $index=0){
		// throws AlfrescoObjectNotFoundException, AlfrescoObjectAlreadyExistsException{

		try{
			$folder=$this->getObject($parentId);
		
		}catch(CmisObjectNotFoundException $e){
			throw new AlfrescoObjectNotFoundException(__("Folder ID [:name] not found in Alfresco", array("name"=>$parentId)));
		}

		$filename=AlfrescoHelper::sanitizeName($filename);

		
		
		//dd($args);
		$return=$this->call('POST','nodes/'.$parentId.'/children', [
			"query"=>['include'=>'path'], 
			"multipart"=>[
				[
					'name' => 'name',
		            'contents' => $filename
				],
				[
					'name' => 'nodeType',
		            'contents' => 'cm:content'
				],
				[
		            'name'     => 'filedata',
		            'contents' => $filecontent,
		            'filename' => $filename
		        ],
				[
		            'name'     => 'autoRename',
		            'contents' =>  $this->isRepeatedRename()?"true":"false"
		        ],
				[
		            'name'     => 'overwrite',
		            'contents' => $this->isRepeatedOverwrite()?"true":"false"
		        ],
		        [
		            'name'     => 'renditions',
		            'contents' => 'pdf'
		        ]
		        
		        

			]
		]);

		$doc=$this->fromRestObject($return);
		
		return $doc;
			
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
		
		//TODO

	}



	
	public function getPath($path){
		return ltrim(substr( $path , strlen($this->rootpath)),"/");
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
		
		$results=$this->call("GET","queries/nodes",[
			"query"=>[
				'include'=>'path',
				"term"=>$query,
				"rootNodeId"=>$folderId
			]
		]);

		return $this->fromRestObjects($results);
		
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


    public function getPreview($id, $type="pdf"){

    	try{
    		$object=$this->getDocument($id);
    		
	    	if($object->isFile()){
	    		if($object->isPdf() || $object->isImage()){
	    			$content=$object->getContent();
	    			$mime=$object->mimetype;
	    			$size=$object->size;
	    		}else{
		 			
		 			$response=$this->call('GET','nodes/'.$id.'/renditions/'.$type);

		 			
			 		if($response && $response->entry && $response->entry->status=="CREATED"){

						$content=$this->call('GET','nodes/'.$id.'/renditions/'.$type.'/content');
			 			$mime=$response->entry->content->mimeType;
	    				$size=$response->entry->content->sizeInBytes;
			 		}
				}

				if(isset($content) && $content){
	 				AlfrescoHelper::download($content, $object->name, $mime, $size, true);
				}
		 	}
	 		return false;

	 	}catch(Exception $e){
	 		return false;
	 	}

 			


    }
    	
		


}