<?php

namespace Ajtarragona\AlfrescoLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Log;

class AlfrescoLaravel extends Model
{
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
}
