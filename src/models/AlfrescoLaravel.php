<?php

namespace Ajtarragona\AlfrescoLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Log;
use Route;
use Illuminate\Http\File;

class AlfrescoLaravel
{
    /**
     * Uploads a file into Alfresco folder
     * @param  Mixed   $file The file to be uploaded, it has to be a Symfony\Component\HttpFoundation\File\UploadedFile, Illuminate\Http\File or a String
     * @return Boolean       Result of the uploading
     */
    public static function upload($file, $name = '', $containerName = ''){
        try {
            //Path
            if(is_string($file)) {
                //Check the file exists
                if(file_exists(public_path().$file)){
                    //Generate file and obtain data
                    $file = new File(public_path().$file);
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
            if($containerName == ''){
                $defaultNodeData = AlfrescoLaravel::getMetadataId(config('alfresco.containerid'));
                $containerName = $defaultNodeData['st:componentId'];
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
     * List all the children of the given node
     * @param  String $node Id of the node to list, by default is -root-
     * @return Array        Array with the id of the parent node and an array with the data of all the childs
     */
    public static function list($node = '-root-'){
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
     * Download a file from Alfresco
     * @param  String $id                Id of the file to download
     * @param  String $destinationFolder Folder route where the file will be storaged
     * @return Mixed                     String with the route to the new file or boolean when fails
     */
    public static function download($id, $destinationFolder){
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
                if($info['http_code'] == '200'){
                    //Check if the folder exists
                    if (!is_dir($destinationFolder)) {                                
                       mkdir($destinationFolder, 0755, true);
                    }
                    //Check if the folder route ends with /
                    if(substr($destinationFolder, strlen($destinationFolder)-1,1) != '/'){
                        $destinationFolder .= '/';
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
     * Check if a node exists in the alfresco repository
     * @param  String  $nodeId Id of the node to search
     * @return Boolean         Result of the search
     */
    public static function existsId($nodeId){
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
    public static function getId($nodeId){
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
            return curl_exec($curl);
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
    public static function getMetadataId($nodeId){
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
    public static function search($term){
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
    private static function getUniqueName($name){
        try {
            $found = false;
            $count = 0;
            $newName = AlfrescoLaravel::sanitizeName($name);
            $pieces = explode('.', $newName);
            while(!$found){
                $data = AlfrescoLaravel::search($newName);
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
    public static function copy($nodeId, $destinationId, $newName = ''){
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
    public static function move($nodeId, $destinationId, $newName = ''){
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
    public static function delete($nodeId, $permanent = false){
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
    public static function createFolder($name, $parentId = null){
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
    public static function put($nodeId, $file){
        try {
            //Get content
            if(is_string($file)) {
                $content = file_get_contents(public_path().$file);
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

    /**
     * Clear the name of a node to prevent errors
     * @param  String $name Name of the node
     * @return String       Sanitized name
     */
    private static function sanitizeName($name){
        return str_replace(' ', '_', $name);
    }
}