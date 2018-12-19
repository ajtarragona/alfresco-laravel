<?php

namespace Ajtarragona\AlfrescoLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Log;
use Route;

class AlfrescoLaravel extends Model
{
    /**
     * Uploads a file into Alfresco folder
     * @param  Symfony\Component\HttpFoundation\File\UploadedFile $file The file to be uploaded
     * @return Boolean                                                  Result of the uploading
     */
    public static function upload($file){
        try {
            $curl = curl_init();
            $uploadFile = curl_file_create($file->path(),
                                            $file->getClientOriginalExtension(),
                                            $file->getClientOriginalName());
            $query = array(
                            'siteid' => config('alfresco.siteid'),
                            'containerid' => config('alfresco.containerid'),
                            'filedata' => $uploadFile
                            );
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.host').'alfresco/service/api/upload',
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
            $result = json_decode($response,true);
            if($result['status']['code'] === '200'){
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
                CURLOPT_URL => config('alfresco.host').'alfresco/api/-default-/public/alfresco/versions/1/nodes/'.$node,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $result = json_decode($response,true);
            if(array_key_exists('error', $result)){
                return array();
            }elseif(isset($result['entry']['parentId'])){
                $return['back'] = $result['entry']['parentId'];
            }
            //Get children
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.host').'alfresco/api/-default-/public/alfresco/versions/1/nodes/'.$node.'/children',
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
     * @return Boolean                   Result of the download
     */
    public static function download($id, $destinationFolder){
        try {
            $curl = curl_init();
            //Get info
            curl_setopt_array($curl, array(
                CURLOPT_URL => config('alfresco.host').'alfresco/api/-default-/public/alfresco/versions/1/nodes/'.$id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_USERPWD => config('alfresco.user').':'.config('alfresco.pass')
            ));
            $response = curl_exec($curl);
            $fileData = json_decode($response,true);
            if(array_key_exists('error', $fileData) || $fileData['entry']['isFolder']){
                $result = false;
            }else{
                //Download
                curl_setopt_array($curl, array(
                    CURLOPT_URL => config('alfresco.host').'alfresco/api/-default-/public/cmis/versions/1.1/atom/content/id?id='.$id,
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
                    $result = true;
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
}
