<?php

namespace Ajtarragona\AlfrescoLaravel\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Alfresco;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoConnectionException;
use Ajtarragona\AlfrescoLaravel\Exceptions\AlfrescoObjectAlreadyExistsException;
use Ajtarragona\AlfrescoLaravel\Models\Helpers\AlfrescoHelper;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoDocument;

class AlfrescoLaravelController extends Controller
{

    protected  function makeBreadcrumb($object,$last=false){
        $bread=$object->getBreadcrumb();
        $breadcrumb=[];

        $breadcrumb[]=[
            "name" =>"",
            "icon" => "home",
            "url" => route("alfresco.explorer")
        ];
        //dump($bread);
        if($bread){
            foreach($bread as $i=>$b){
                $url=route("alfresco.explorer",[$b["path"]]);
                
                if(!$last && $i==(count($bread)-1)) $url=false;

                $breadcrumb[]=[
                    "name" => $b["name"],
                    "url" => $url
                ];
            }
            // $breadcrumb[]=[
            //     "name" =>$folder->name
            // ];
        }
        if($last){
             $breadcrumb[]=[
                "name" => $last
            ];
        }
        return $breadcrumb;

    }




    public function explorer($path=false, Request $request){
        
       
        //dd($path);
        try{

            if($path){
                $folder=Alfresco::getFolderByPath($path);
            }else{
                $folder=Alfresco::getBaseFolder();
            }

            $children=$folder->getChildren();

            $sortfield=isset($request->sort)?$request->sort:"NAME";
            $sortdir=isset($request->direction)?$request->direction:"ASC";

            $children=AlfrescoHelper::sort($children,$sortfield,$sortdir);

            $search_term=session('search_term','');
            $search_recursive=session('search_recursive',false);
             
            $breadcrumb=$this->makeBreadcrumb($folder);
            
            $params = compact('path','sortfield','sortdir','folder','children','breadcrumb','search_term','search_recursive');

            return view("alfresco::explorer",$params);

        }catch(AlfrescoConnectionException $e){
           return view("alfresco::error",["error"=>$e->getMessage()]);
        }
    }



  

    public function download($id)
    {
        
        Alfresco::downloadObject($id);

    }


    public function delete($id)
    {
        $obj=Alfresco::getObject($id);
        $path=$obj->getParent()->path;
        
        

        $ret=Alfresco::delete($id);

        if($ret){
            return redirect()
                    ->route('alfresco.explorer',[$path])
                    ->with(['success'=>"esborrat correctament"]);
        }else{
             return redirect()
                    ->route('alfresco.explorer',[$path])
                    ->with(['error'=>"No s'ha pogut esborrar"]);
        }


    }

    public function viewDocument($id)
    {
        Alfresco::downloadObject($id,true);

    }


    public function previewDocument($id)
    {
        Alfresco::getPreview($id);

    }

    public function info($id)
    {
        $object=Alfresco::getObject($id);
        //dd($object);
        $attributes=$object->getAttributes();
       
        $breadcrumb=$this->makeBreadcrumb($object);
        $params=compact("object","breadcrumb",'attributes');

        return view("alfresco::info",$params);
    }







    public function addmodal($id){
        $folder=Alfresco::getObject($id);
        $params=compact('folder');

        return view("alfresco::modal.add",$params);
    }

    public function add($id, Request $request){
        //dd($request->all());
       
        $documents = $request->file('documents');
        $folder=Alfresco::getObject($id);

                
        $path=$folder->path;
        $docs=$request->documents;
        if($docs){
            $ret = Alfresco::upload($id,$docs);

            if($ret){
                $ok=[];
                $err=[];

                foreach($ret as $r){
                    if($r instanceof AlfrescoDocument){
                        $ok[]=__("Arxiu <strong>:name</strong> pujat correctament",["name"=>$r->name]);
                    }else{
                        $err[]=$r;
                    }
                }
               
                $return=redirect()->route('alfresco.explorer',[$path]);

                if($ok) $return->with('success', implode("<br/>", $ok));
                if($err) $return->with('error', implode("<br/>", $err));

                return $return;


            }else{
                 return redirect()
                        ->route('alfresco.explorer',[$path])
                        ->with(['error'=>"No s'han pogut pujar els arxius"]);
            }
        }else{
            return redirect()
                    ->route('alfresco.explorer',[$path]);
        }
        
    }






    public function createfoldermodal($id){
        $folder=Alfresco::getObject($id);
        $params=compact('folder');

        return view("alfresco::modal.createfolder",$params);
    }



    public function createfolder($id, Request $request){
        //dd($request->all());
        $name = $request->name;
        $parent=Alfresco::getObject($id);

        $path=$parent->path;
        try{
            $folder=$parent->createFolder($name);
            
            if($folder){
                return redirect()
                    ->route('alfresco.explorer',[$path])
                    ->with(['success'=>__("Carpeta creada correctament amb el nom <strong>:name</strong>",["name"=>$folder->name])]);
            }else{
                 return redirect()
                    ->route('alfresco.explorer',[$path])
                    ->with(['error'=>"No s'ha pogut crear la carpeta"]);
            }
        }catch(AlfrescoObjectAlreadyExistsException $e){
             return redirect()
                ->route('alfresco.explorer',[$path])
                ->with(['error'=>"Ja existeix una carpeta amb el mateix nom"]);
        }
    }




    public function renamemodal($id){
        $object=Alfresco::getObject($id);
       // dd($object);
        $params=compact('object');

        return view("alfresco::modal.rename",$params);
    }





    public function rename($id, Request $request){
        //dd($request->all());
        $name = $request->name;
        $object=Alfresco::getObject($id);
        $parent=$object->getParent();
        $path=$parent->path; 

        try{
            $object=$object->rename($name);
           // dd($object);
            if($object){
                $type=$object->isFolder()?__("Directori"):__("Arxiu");
                return redirect()
                    ->route('alfresco.explorer',[$path])
                    ->with(['success'=>__(":type renombrat a <strong>:name</strong>",["type"=>$type,"name"=>$object->name])]);
            }else{
                 return redirect()
                    ->route('alfresco.explorer',[$path])
                    ->with(['error'=>"No s'ha pogut renombrar"]);
            }
        }catch(AlfrescoObjectAlreadyExistsException $e){
            return redirect()
                ->route('alfresco.explorer',[$path])
                ->with(['error'=>"Ja existeix un objecte amb el mateix nom"]);
        }
    }






    public function search($id,Request $request){

         session(['search_parent_id'=>$id]);
         session(['search_term'=>$request->term]);
         session(['search_recursive'=>$request->recursive]);


         return redirect()->route('alfresco.searchresults');
    }

    public function searchresults(Request $request){

        $search_parent_id=session('search_parent_id',false);
        $search_term=session('search_term','');
        $search_recursive=session('search_recursive',false);
        
        $folder=Alfresco::getFolder($search_parent_id);

        $breadcrumb=$this->makeBreadcrumb($folder, icon('search')." ".$search_term );
        //dd($breadcrumb);
        $results=[];
        if($search_term && strlen($search_term)>2){
            $results=Alfresco::search($search_term, $search_parent_id, $search_recursive);
        
       // dd($results);

            $sortfield=isset($request->sort)?$request->sort:"NAME";
            $sortdir=isset($request->direction)?$request->direction:"ASC";

            $results=AlfrescoHelper::sort($results,$sortfield,$sortdir);
        }
       // dd($results);

        $params=compact('folder','breadcrumb','search_term','search_recursive','results');

        return view("alfresco::searchresults",$params);
    }


    public function copymodal($id, Request $request){
        
        $folder=Alfresco::getObject($id);
        $path=$folder->path;
        //$basefolder=Alfresco::getBaseFolder();

        $selected=$request->selected;

        $folders = [Alfresco::getBaseFolder()];//->getChildren("folder");
        $params=compact('folder','selected','folders');

        return view("alfresco::modal.copy",$params);
    }

    public function movemodal($id,Request $request){
        $folder=Alfresco::getObject($id);
        $path=$folder->path;
  
        $selected=$request->selected;
        $folders = [Alfresco::getBaseFolder()];//->getChildren("folder");
        $params=compact('folder','selected','folders');
        
        return view("alfresco::modal.move",$params);
    }

    public function tree($id, Request $request){
        
        $folders = Alfresco::getFolder($id)->getFolders();
        $params=compact('folders');

        if(isset($request->currentFolderId)){
            $params["folder"]=Alfresco::getFolder($request->currentFolderId);
        }
        
        return view("alfresco::parts.tree",$params);
    }

    public function batch($id, Request $request){
        
        $folder=Alfresco::getObject($id);
        $path=$folder->path;
        $selected=json_decode($request->selected);
        
        
        $ok=[];
        $err=[];
        if($selected){
            switch($request->submitaction){
                case "copy":
                    if($request->folderId){
                        $folderId=$request->folderId;
                        foreach($selected as $fid){
                            $copied=Alfresco::getObject($fid);
                            try{
                                $ret=Alfresco::copy($fid, $folderId);
                                if($ret) $ok[]=__("Arxiu <strong>:name</strong> copiat correctament",["name"=>$ret->name]);
                                else $err[]=__("Error copiant l'arxiu <strong>:name</strong>",["name"=>$ret->name]);
                            }catch(AlfrescoObjectAlreadyExistsException $e){
                                $err[]=__("Ja existeix un arxiu amb el mateix nom <strong>:name</strong> a la carpeta destí",["name"=>$copied->name]);
                            }
                        }  
                    }
                    break;
                case "move":
                    if($request->folderId){
                        $folderId=$request->folderId;
                        foreach($selected as $fid){
                            $copied=Alfresco::getObject($fid);
                            try{
                                $ret=Alfresco::move($fid, $folderId);
                                if($ret) $ok[]=__("Arxiu <strong>:name</strong> mogut correctament",["name"=>$ret->name]);
                                else $err[]=__("Error movent l'arxiu <strong>:name</strong>",["name"=>$ret->name]);
                            }catch(AlfrescoObjectAlreadyExistsException $e){
                               $err[]=__("Ja existeix un arxiu amb el mateix nom <strong>:name</strong> a la carpeta destí",["name"=>$copied->name]);
                            }
                        }
                    }
                    break;
                case "delete":
                    foreach($selected as $fid){
                        $ret=Alfresco::delete($fid);
                        if($ret) $ok[]=__("Arxiu <strong>:id</strong> esborrat correctament",["id"=>$fid]);
                        else $err[]=__("Error esborrant l'arxiu <strong>:id</strong>",["id"=>$fid]);
                    }
                    break;
                default:break;
            }

        }

        $return=redirect()->route('alfresco.explorer',[$path]);
         
        if($ok) $return->with('success', implode("<br/>", $ok));
        if($err) $return->with('error', implode("<br/>", $err));

        return $return;
    }


}