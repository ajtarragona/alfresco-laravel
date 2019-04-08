<?php

namespace Ajtarragona\AlfrescoLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Log;
use Route;
use Illuminate\Http\File;

class AlfrescoLaravel
{

    
    /**
     * Functions by config
    */
    public static function upload ($file, $containerId = '', $name = ''){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::uploadRest($file, $containerId, $name);
        } else {
            return AlfrescoLaravel::uploadCMIS($file, $containerId, $name);
        }
    }
    public static function download ($id, $destinationFolder){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::downloadRest($id, $destinationFolder);
        } else {
            return AlfrescoLaravel::downloadCMIS($id, $destinationFolder);
        }
    }
    public static function list ($node = '-root-'){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::listRest($node);
        } else {
            return AlfrescoLaravel::listCMIS($node);
        }
    }
    public static function existsId ($nodeId){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::existsIdRest($nodeId);
        } else {
            return AlfrescoLaravel::existsIdCMIS($nodeId);
        }
    }
    public static function getId ($nodeId){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::getIdRest($nodeId);
        } else {
            return AlfrescoLaravel::getIdCMIS($nodeId);
        }
    }
    public static function getMetadataId ($nodeId){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::getMetadataIdRest($nodeId);
        } else {
            return AlfrescoLaravel::getMetadataIdCMIS($nodeId);
        }
    }
    public static function search ($term, $strict = false){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::searchRest($term);
        } else {
            return AlfrescoLaravel::searchCMIS($term, $strict);
        }
    }
    public static function getUniqueName ($name){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::getUniqueNameRest($name);
        } else {
            return AlfrescoLaravel::getUniqueNameCMIS($name);
        }
    }
    public static function copy ($nodeId, $destinationId, $newName = ''){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::copyRest($nodeId, $destinationId, $newName);
        } else {
            return AlfrescoLaravel::copyCMIS($nodeId, $destinationId, $newName);
        }
    }
    public static function move ($nodeId, $destinationId, $newName = ''){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::moveRest($nodeId, $destinationId, $newName);
        } else {
            return AlfrescoLaravel::moveCMIS($nodeId, $destinationId, $newName);
        }
    }
    public static function delete ($nodeId, $permanent = false){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::deleteRest($nodeId, $permanent);
        } else {
            return AlfrescoLaravel::deleteCMIS($nodeId);
        }
    }
    public static function createFolder ($name, $parentId = null){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::createFolderRest($name, $parentId);
        } else {
            return AlfrescoLaravel::createFolderCMIS($name, $parentId);
        }
    }
    public static function put ($nodeId, $file){
        if(config('alfresco.use_rest')){
            return AlfrescoLaravel::putRest($nodeId, $file);
        } else {
            return AlfrescoLaravel::putCMIS($nodeId, $file);
        }
    }

    /*****************************************************************
    ****    CCCCC    OOOOO    MM MM    MM MM    OOOOO    N   N    ****
    ****    C        O   O    M M M    M M M    O   O    NN  N    ****
    ****    C        O   O    M M M    M M M    O   O    N N N    ****
    ****    C        O   O    M   m    M   m    O   O    N  NN    ****
    ****    CCCCC    OOOOO    M   M    M   M    OOOOO    N   N    ****
    *****************************************************************/

    /**
     * Clear the name of a node to prevent errors
     * @param  String $name Name of the node
     * @return String       Sanitized name
     */
    private static function sanitizeName($name){
        return str_replace(' ', '_', $name);
    }

    /***********************************************
    ****    RRRRR    EEEEE    SSSSS    TTTTT    ****
    ****    R   R    E        S          T      ****
    ****    RRRRR    EEEEE    SSSSS      T      ****
    ****    R  R     E            S      T      ****
    ****    R   R    EEEEE    SSSSS      T      ****
    ***********************************************/

    /**
     * Uploads a file into Alfresco folder
     * @param  Mixed    $file           The file to be uploaded, it has to be a Symfony\Component\HttpFoundation\File\UploadedFile, Illuminate\Http\File or a String
     * @param  String   $containerId    Id of the folder where the file will be uploaded
     * @param  String   $name           New name for the file, if none is supplied the original name is kept
     * @return Mixed                    Id of the new file or boolean
     */
    public static function uploadRest($file, $containerId, $name){
        try {
            //Path
            if(is_string($file)) {
                //Check the file exists
                if(strpos($file, public_path()) !== 0){
                    $file = public_path().$file;
                }
                if(file_exists($file)){
                    //Generate file and obtain data
                    $file = new File($file);
                    $path = $file->path();
                    $extension = $file->extension();
                    if($name == ''){
                        $pathPieces = explode(DIRECTORY_SEPARATOR, $path);
                        $name = end($pathPieces);
                    }
                } else {
                    return false;
                }
            } elseif(get_class($file) == 'Illuminate\Http\UploadedFile') {
                $path = $file->path();
                $extension = $file->getClientOriginalExtension();
                $name = $name == '' ? $file->getClientOriginalName() : $name;
            } else {
                $path = $file->path();
                $extension = $file->extension();
                if($name == ''){
                    $pathPieces = explode(DIRECTORY_SEPARATOR, $path);
                    $name = end($pathPieces);
                }
            }
            //Check that the name contains the extension
            if(count(explode('.', $name)) < 2){
                $name .= '.'.$extension;            
            }
            $name = AlfrescoLaravel::getUniqueName($name);
            $curl = curl_init();
            //Prepare file
            $uploadFile = curl_file_create($path,
                                            $extension,
                                            $name);

            //Prepare container
            if($containerId == ''){
                $defaultNodeData = AlfrescoLaravel::getMetadataId(config('alfresco.containerid'));
                $containerName = $defaultNodeData['st:componentId'];
            } else {
                $containerData = AlfrescoLaravel::getMetadataId($containerId);
                $containerName = $containerData['st:componentId'];
            }
            
            //Prepare other data
            $query = array(
                            'siteid' => config('alfresco.siteid'),
                            'containerid' => $containerName,
                            'filedata' => $uploadFile
                            );
            //Upload
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'service/api/upload',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $query,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            //Check result
            $result = json_decode($response,true);
            if($result['status']['code'] === 200){
                $pieces = explode('/', $result['nodeRef']);
                return end($pieces);
            } else {
                return false;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Download a file from Alfresco
     * @param  String $id                Id of the file to download
     * @param  String $destinationFolder Folder route where the file will be storaged
     * @return Mixed                     String with the route to the new file or boolean when fails
     */
    public static function downloadRest($id, $destinationFolder){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $fileData = json_decode($response,true);
            if(!is_array($fileData) || array_key_exists('error', $fileData) || $fileData['entry']['isFolder']){
                $result = false;
            }else{
                //Download
                curl_setopt_array($curl, array(
                    CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/atom/content/id?id='.$id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
                ));
                $response = curl_exec($curl);
                $info = curl_getinfo($curl);
                if(substr($destinationFolder, 0, 1) != DIRECTORY_SEPARATOR){
                    $destinationFolder = public_path().DIRECTORY_SEPARATOR.$destinationFolder;
                } else {
                    $destinationFolder = public_path().$destinationFolder;
                }
                if($info['http_code'] == '200'){
                    //Check if the folder exists
                    if (!is_dir($destinationFolder)) {                                
                       mkdir($destinationFolder, 0755, true);
                    }
                    //Check if the folder route ends with /
                    if(substr($destinationFolder, strlen($destinationFolder)-1,1) != DIRECTORY_SEPARATOR){
                        $destinationFolder .= DIRECTORY_SEPARATOR;
                    }
                    file_put_contents($destinationFolder.$fileData['entry']['name'], $response);
                    $result = $destinationFolder.$fileData['entry']['name'];
                } else {
                    $result = false;
                }
            }
            curl_close($curl);
            return $result;
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }
    
    /**
     * List all the children of the given node
     * @param  String $node Id of the node to list, by default is -root-
     * @return Array        Array with the id of the parent node and an array with the data of all the childs
     */
    private static function listRest($node = '-root-'){
        try {
            $curl = curl_init();
            $return = array();
            //Get current
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$node,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $result = json_decode($response,true);
            if(!is_array($result) || array_key_exists('error', $result)){
                return array();
            }elseif(isset($result['entry']['parentId'])){
                $return['back'] = $result['entry']['parentId'];
            }
            //Get children
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$node.'/children',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $result = json_decode($response,true);
            foreach ($result['list']['entries'] as $element) {
                $return['children'][] = array(
                                                'id' => $element['entry']['id'],
                                                'name' => $element['entry']['name'],
                                                'isFolder' => $element['entry']['isFolder']
                                            );
            }

            return $return;
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return array();
        }
    }

    /**
     * Check if a node exists in the alfresco repository
     * @param  String  $nodeId Id of the node to search
     * @return Boolean         Result of the search
     */
    private static function existsIdRest($nodeId){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $fileData = json_decode($response,true);
            if(!is_array($fileData) || array_key_exists('error', $fileData) || $fileData['entry']['isFolder']){
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Obtains the binary content of a node
     * @param  String  $nodeId Id of the node to search
     * @return Mixed           Binary content of the node or boolean
     */
    private static function getIdRest($nodeId){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$nodeId.'/content',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $content = curl_exec($curl);
            $info = curl_getinfo($curl);
            if($info['http_code'] == 200){
                return $content;
            } else {
                return false;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Obtains the metadata of a node
     * @param  String  $nodeId Id of the node to search
     * @return Mixed           Array with the metadata of the node or boolean
     */
    private static function getMetadataIdRest($nodeId){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$nodeId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $fileData = json_decode($response,true);
            if(!is_array($fileData) || array_key_exists('error', $fileData)){
                return false;
            } else {
                if($fileData['entry']['isFolder']){
                    return $fileData['entry']['properties'];
                } else {
                    $extraData = array(
                                    'isFolder' => $fileData['entry']['isFolder'],
                                    'name' => $fileData['entry']['name']
                                    );
                    return array_merge($fileData['entry']['content'],$fileData['entry']['properties'], $extraData);
                }
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Search a node by his name
     * @param  String $term Name of the node to search
     * @return Mixed        Array with the result of the search or boolean
     */
    private static function searchRest($term){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/queries/nodes?term='.$term,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $data = json_decode($response,true);
            if(!is_array($data) || array_key_exists('error', $data)){
                return false;
            } else {
                return $data;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Find a unique name for a file
     * @param  String $name Original name of the file
     * @return String       Unique name of the file
     */
    private static function getUniqueNameRest($name){
        try {
            $found = false;
            $count = 0;
            $newName = AlfrescoLaravel::sanitizeName($name);
            $pieces = explode('.', $newName);
            while(!$found){
                $data = AlfrescoLaravel::search($newName, true);
                if(empty($data['list']['entries'])){
                    $found = true;
                } else {
                    $count++;
                    $newName = $pieces[0].'_'.$count;
                    //We add the other pieces, in a for because image.png has 2 pieces but view.blade.php has 3
                    for ($i=1; $i < count($pieces); $i++) { 
                        $newName .= '.'.$pieces[$i];
                    }
                }
            }
            return $newName;
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Copies a node to a new location
     * @param  String  $nodeId        Id of the node to copy
     * @param  String  $destinationId Id of the node where the new node will be created
     * @param  String  $newName       Name of the new node (optional)
     * @return Boolean                Result of the copy
     */
    private static function copyRest($nodeId, $destinationId, $newName = ''){
        try {
            $originalData = AlfrescoLaravel::getMetadataId($nodeId);
            if($newName != ''){
                $newName = AlfrescoLaravel::getUniqueName($newName);
                if(!$originalData['isFolder'] && count(explode('.', $newName)) < 2){
                    //Add extension
                    $pieces = explode('.', $originalData['name']);
                    $newName .= '.'.end($pieces);
                }
            } else {
                $newName = AlfrescoLaravel::getUniqueName($originalData['name']);
            }
            $params = array(
                'targetParentId' => $destinationId,
                'name'           => $newName
            );
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$nodeId.'/copy',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($params),
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $data = json_decode($response,true);
            if(!is_array($data) || array_key_exists('error', $data)){
                return false;
            } else {
                return $data['entry']['id'];
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Move a node to a new location
     * @param  String  $nodeId        Id of the node to move
     * @param  String  $destinationId Id of the node where the original node will be moved
     * @param  String  $newName       New name of the node (optional)
     * @return Boolean                Result of the movement
     */
    private static function moveRest($nodeId, $destinationId, $newName = ''){
        try {
            $params = array(
                'targetParentId' => $destinationId
            );
            if($newName != ''){
                $newName = AlfrescoLaravel::getUniqueName($newName);
                $originalData = AlfrescoLaravel::getMetadataId($nodeId);
                if(!$originalData['isFolder'] && count(explode('.', $newName)) < 2){
                    //Add extension
                    $pieces = explode('.', $originalData['name']);
                    $newName .= '.'.end($pieces);
                }
                $params['name'] = $newName;
            }
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$nodeId.'/move',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($params),
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $data = json_decode($response,true);
            if(!is_array($data) || array_key_exists('error', $data)){
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Deletes a node, if it's a folder, it also delete the contents
     * @param  String  $nodeId    Id of the node to delete
     * @param  Boolean $permanent Indicates if the delete is permanent or we send it to the trascan
     * @return Boolean            Result of the deletion
     */
    private static function deleteRest($nodeId, $permanent = false){
        try {

            $permanent ? 'true' : 'false';

            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$nodeId.'?permanent='.$permanent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $data = json_decode($response,true);
            if(is_array($data) && array_key_exists('error', $data)){
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Create a folder
     * @param  String  $name     Name of the new folder
     * @param  String  $parentId Id of the parent folder, if none is supplied, the default folder will be user
     * @return Mixed             Id of the new folder or boolean
     */
    private static function createFolderRest($name, $parentId = null){
        try {
            $folderData = array(
                'name' => AlfrescoLaravel::getUniqueName($name),
                'nodeType' => 'cm:folder'
            );
            if(!$parentId || !AlfrescoLaravel::existsId($parentId)){
                $parentId = config('alfresco.containerid');
            }
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$parentId.'/children',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($folderData),
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $data = json_decode($response,true);
            if(!is_array($data) || array_key_exists('error', $data)){
                return false;
            } else {
                return $data['entry']['id'];
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Update the content of a node
     * @param  String $nodeId Id of the node to be updated
     * @param  Mixed  $file   The file to extract the new content, it has to be a Symfony\Component\HttpFoundation\File\UploadedFile, Illuminate\Http\File or a String
     * @return Boolean        Result of the update
     */
    private static function putRest($nodeId, $file){
        try {
            //Get content
            if(is_string($file)) {
                if(strpos($file, public_path()) !== 0){
                    if(substr($file, 1,1) != DIRECTORY_SEPARATOR){
                        $file = public_path().DIRECTORY_SEPARATOR.$file;
                    } else {
                        $file = public_path().$file;
                    }
                }
                if(file_exists($file)){
                    $content = file_get_contents($file);
                } else {
                    return false;
                }
            } else {
                $path = $file->path();
                $content = file_get_contents($path);
            }
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/alfresco/versions/1/nodes/'.$nodeId.'/content',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => $content,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $data = json_decode($response,true);
            if(!is_array($data) || array_key_exists('error', $data)){
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /*******************************************
    ****    CCCCC    MM MM    I    SSSSS    ****
    ****    C        M M M    I    S        ****
    ****    C        M M M    I    SSSSS    ****
    ****    C        M   m    I        S    ****
    ****    CCCCC    M   M    I    SSSSS    ****
    *******************************************/

    /**
     * Uploads a file into Alfresco folder
     * @param  Mixed    $file           The file to be uploaded, it has to be a Symfony\Component\HttpFoundation\File\UploadedFile, Illuminate\Http\File or a String
     * @param  String   $containerId    Id of the folder where the file will be uploaded
     * @param  String   $name           New name for the file, if none is supplied the original name is kept
     * @return Mixed                    Id of the new file or boolean
     */
    private static function uploadCmis($file, $containerId, $name){
        try {
            //Path
            if(is_string($file)) {
                //Check the file exists
                if(strpos($file, public_path()) !== 0){
                    if(substr($file, 1,1) != DIRECTORY_SEPARATOR){
                        $file = public_path().DIRECTORY_SEPARATOR.$file;
                    } else {
                        $file = public_path().$file;
                    }
                }
                if(file_exists($file)){
                    //Generate file and obtain data
                    $file = new File($file);
                    $path = $file->path();
                    $extension = $file->extension();
                    if($name == ''){
                        $pathPieces = explode(DIRECTORY_SEPARATOR, $path);
                        $name = end($pathPieces);
                    }
                } else {
                    return false;
                }
            } elseif(get_class($file) == 'Illuminate\Http\UploadedFile') {
                $path = $file->path();
                $extension = $file->getClientOriginalExtension();
                $name = $name == '' ? $file->getClientOriginalName() : $name;
            } else {
                $path = $file->path();
                $extension = $file->extension();
                if($name == ''){
                    $pathPieces = explode(DIRECTORY_SEPARATOR, $path);
                    $name = end($pathPieces);
                }
            }
            //Check that the name contains the extension
            if(count(explode('.', $name)) < 2){
                $name .= '.'.$extension;            
            }
            $name = AlfrescoLaravel::getUniqueName($name);

            //Prepare data
            $query = array(
                        'cmisaction' => 'createDocument',
                        'propertyId' => array(
                                            0 => 'cmis:objectTypeId',
                                            1 => 'cmis:name'
                                        ),
                        'propertyValue' => array(
                                                0 => 'cmis:document',
                                                1 => $name,
                                            )
                        );
            //Create document
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/browser?objectId='.$containerId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => urldecode(http_build_query($query)),
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            //Check result
            $result = json_decode($response,true);
            if(!is_array($result) || isset($result['exception'])){
                return false;
            } else {
                $pieces = explode('/', $result['properties']['alfcmis:nodeRef']['value']);
                $newNodeId = end($pieces);
                //Upload Content
                if(AlfrescoLaravel::put($newNodeId,$file)){
                    return $newNodeId;
                } else {
                    //If the upload fails, delete the new node
                    AlfrescoLaravel::delete($newNodeId);
                    return false;
                }
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Download a file from Alfresco
     * @param  String $id                Id of the file to download
     * @param  String $destinationFolder Folder route where the file will be storaged
     * @return Mixed                     String with the route to the new file or boolean when fails
     */
    public static function downloadCMIS($id, $destinationFolder){
        try {
            $curl = curl_init();
            //Get info
            $fileData = AlfrescoLaravel::getMetadataId($id);
            if(!is_array($fileData) || $fileData['objectTypeId'] != 'cmis:document'){
                $result = false;
            }else{
                //Download
                curl_setopt_array($curl, array(
                    CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/atom/content/id?id='.$id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
                ));
                $response = curl_exec($curl);
                $info = curl_getinfo($curl);
                if(substr($destinationFolder, 0, 1) != DIRECTORY_SEPARATOR){
                    $destinationFolder = public_path().DIRECTORY_SEPARATOR.$destinationFolder;
                } else {
                    $destinationFolder = public_path().$destinationFolder;
                }
                if($info['http_code'] == '200'){
                    //Check if the folder exists
                    if (!is_dir($destinationFolder)) {                                
                       mkdir($destinationFolder, 0755, true);
                    }
                    //Check if the folder route ends with /
                    if(substr($destinationFolder, strlen($destinationFolder)-1,1) != DIRECTORY_SEPARATOR){
                        $destinationFolder .= DIRECTORY_SEPARATOR;
                    }
                    file_put_contents($destinationFolder.$fileData['name'], $response);
                    $result = $destinationFolder.$fileData['name'];
                } else {
                    $result = false;
                }
            }
            curl_close($curl);
            return $result;
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * List all the children of the given node
     * @param  String $node Id of the node to list
     * @return Array        Array with the id of the parent node and an array with the data of all the childs
     */
    private static function listCMIS($node = ''){
        try {
            if($node == ''){
                $node = config('alfresco.containerid');
            }
            $curl = curl_init();
            $return = array();
            //Get parent
            dump(config('alfresco'));
            dump(config('alfresco.url'));
            dd(config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/atom/id?id='.$node);
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/atom/id?id='.$node,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $xml = simplexml_load_string($response);
            $return['back'] = (string) $xml->xpath('//cmis:properties/cmis:propertyId[@localName="parentId"]')[0]->children('cmis',true)->value;
            //Get children
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/browser/root?objectId='.$node,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            $result = json_decode($response,true);
            foreach ($result['objects'] as $element) {
                $isFolder = $element['object']['properties']['cmis:baseTypeId']['value'] == 'cmis:folder';
                $return['children'][] = array(
                                                'id' => $element['object']['properties']['cmis:objectId']['value'],
                                                'name' => $element['object']['properties']['cmis:name']['value'],
                                                'isFolder' => $isFolder
                                            );
            }

            return $return;
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return array();
        }
    }

    /**
     * Check if a node exists in the alfresco repository
     * @param  String  $nodeId Id of the node to search
     * @return Boolean         Result of the search
     */
    private static function existsIdCMIS($nodeId){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/browser/root?objectId='.$nodeId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $info = curl_getinfo($curl);
            if($info['http_code'] == 200){
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Obtains the binary content of a node
     * @param  String  $nodeId Id of the node to search
     * @return Mixed           Binary content of the node or boolean
     */
    private static function getIdCMIS($nodeId){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/browser/root?cmisaction=getProperties&objectId='.$nodeId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $content = curl_exec($curl);
            $info = curl_getinfo($curl);
            if($info['http_code'] == 200){
                return $content;
            } else {
                return false;
            } 
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Obtains the metadata of a node
     * @param  String  $nodeId Id of the node to search
     * @return Mixed           Array with the metadata of the node or boolean
     */
    private static function getMetadataIdCMIS($nodeId){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/atom/id?id='.$nodeId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $info = curl_getinfo($curl);
            if($info['http_code'] == 200){
                $xml = simplexml_load_string($response);
                $properties = $xml->xpath('//cmis:properties')[0]->children('cmis',true);
                $result = array();
                foreach ($properties as $key => $prop) {
                    $result[substr($prop->attributes(), strpos($prop->attributes(), ':')+1)] = (string)$prop->value;
                }
                return $result;
            } else {
                return false;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Search a node by his name
     * @param  String  $term   Name of the node to search
     * @param  Boolean $strict Indicates if we are looking for the exact term
     * @return Mixed           Array with the result of the search or boolean
     */
    private static function searchCMIS($term, $strict){
        try {
            if(!$strict){
                $term = '%'.$term.'%';
            }
            $curl = curl_init();
            //Get info
            $query = urlencode("select * from cmis:document where cmis:name LIKE '".$term."'");
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/browser/?cmisselector=query&q='.$query,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $data = json_decode($response,true);
            if(!is_array($data)){
                return false;
            } else {
                return $data;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Find a unique name for a file
     * @param  String $name Original name of the file
     * @return String       Unique name of the file
     */
    private static function getUniqueNameCMIS($name){
        try {
            $found = false;
            $count = 0;
            $newName = AlfrescoLaravel::sanitizeName($name);
            $pieces = explode('.', $newName);
            while(!$found){
                $data = AlfrescoLaravel::search($newName);
                if(empty($data['results'])){
                    $found = true;
                } else {
                    $count++;
                    $newName = $pieces[0].'_'.$count;
                    //We add the other pieces, in a for because image.png has 2 pieces but view.blade.php has 3
                    for ($i=1; $i < count($pieces); $i++) { 
                        $newName .= '.'.$pieces[$i];
                    }
                }
            }
            return $newName;
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Copies a node to a new location
     * @param  String  $nodeId        Id of the node to copy
     * @param  String  $destinationId Id of the node where the new node will be created
     * @param  String  $newName       Name of the new node (optional)
     * @return Boolean                Result of the copy
     */
    private static function copyCMIS($nodeId, $destinationId, $newName){
        try {
            //Get info
            $fileData = AlfrescoLaravel::getMetadataId($nodeId);
            if(!is_array($fileData) || $fileData['objectTypeId'] != 'cmis:document'){
                $result = false;
            }else{
                //Download
                $path = AlfrescoLaravel::download($nodeId,'/uploads/alfresco/tmp');
                if($path){
                    //Upload new file
                    if($newName == ''){
                        $newName = $fileData['name'];
                    }
                    $result = AlfrescoLaravel::upload($path,$destinationId,$newName);
                } else {
                    $result = false;
                }
            }
            return $result;
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Move a node to a new location
     * @param  String  $nodeId        Id of the node to move
     * @param  String  $destinationId Id of the node where the original node will be moved
     * @param  String  $newName       New name of the node (optional)
     * @return String                 Id of the new node
     */
    private static function moveCMIS($nodeId, $destinationId, $newName = ''){
        try {
            //We copy the new file
            $newId = AlfrescoLaravel::copy($nodeId, $destinationId, $newName);
            if($newId){
                //If the copy is successfull, delete the old document
                AlfrescoLaravel::delete($nodeId);
                return $newId;
            } else {
                return false;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Deletes a document
     * @param  String  $nodeId    Id of the node to delete
     * @return Boolean            Result of the deletion
     */
    private static function deleteCMIS($nodeId){
        try {

            $params = array(
                            'cmisaction' => 'delete',
                            'objectId' => $nodeId
                            );
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/browser/root',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => urldecode(http_build_query($params)),
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $data = json_decode($response,true);
            $info = curl_getinfo($curl);
            if(is_array($data) && $info['http_code'] != 200){
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Create a folder
     * @param  String  $name     Name of the new folder
     * @param  String  $parentId Id of the parent folder, if none is supplied, the default folder will be user
     * @return Mixed             Id of the new folder or boolean
     */
    private static function createFolderCMIS($name, $parentId = null){
        try {
            //Prepare data
            $query = array(
                        'cmisaction' => 'createFolder',
                        'propertyId' => array(
                                            0 => 'cmis:objectTypeId',
                                            1 => 'cmis:name',
                                        ),
                        'propertyValue' => array(
                                                0 => 'cmis:folder',
                                                1 => $name,
                                            )
                        );
            //Create document
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/browser/root?objectId='.$parentId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => urldecode(http_build_query($query)),
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            //Check result
            $result = json_decode($response,true);
            if(!is_array($result) || isset($result['exception'])){
                return false;
            } else {
                $pieces = explode('/', $result['properties']['alfcmis:nodeRef']['value']);
                return end($pieces);
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }

    /**
     * Update the content of a node
     * @param  String $nodeId Id of the node to be updated
     * @param  Mixed  $file   The file to extract the new content, it has to be a Symfony\Component\HttpFoundation\File\UploadedFile, Illuminate\Http\File or a String
     * @return Boolean        Result of the update
     */
    private static function putCMIS($nodeId, $file){
        try {

            //Get content
            if(is_string($file)) {
                if(strpos($file, public_path()) !== 0){
                    if(substr($file, 1,1) != DIRECTORY_SEPARATOR){
                        $file = public_path().DIRECTORY_SEPARATOR.$file;
                    } else {
                        $file = public_path().$file;
                    }
                }
                if(file_exists($file)){
                    $content = file_get_contents($file);
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file);
                } else {
                    return false;
                }
            } else {
                $path = $file->path();
                $content = file_get_contents($path);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $path);
            }
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.url').'api/'.config('alfresco.repository_id').'/public/cmis/versions/1.1/atom/content?id='.$nodeId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => array('Content-Type:'.$mime),
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => $content,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $info = curl_getinfo($curl);
            if($info['http_code'] != 201){
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            Log::error('*****************************************************************************************');
            Log::error('Error: '.$e->getMessage().' ******* In '.Route::currentRouteAction());
            Log::error('*****************************************************************************************');
            return false;
        }
    }
}