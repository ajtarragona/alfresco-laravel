<?php

namespace Ajtarragona\AlfrescoLaravel\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Alfresco;
use Ajtarragona\AlfrescoLaravel\Models\Helpers\AlfrescoHelper;
use Ajtarragona\AlfrescoLaravel\Models\AlfrescoDocument;
use Exception;
use Illuminate\Http\Exceptions\PostTooLargeException;

class AlfrescoLaravelController extends Controller
{

    protected  function makeBreadcrumb($object,$last=false){
        $bread=$object->getBreadcrumb();

        $breadcrumb=[];

        $breadcrumb[]=[
            "name" =>"",
            "icon" => "home",
            "url" => route("alfresco.show")
        ];
        //dump($bread);
        if($bread){
            foreach($bread as $i=>$b){
                $url=route("alfresco.show",[$b["path"]]);
                
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
    public function show($path=false, Request $request){
        //dd($path);

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
        
        $params=compact('path','sortfield','sortdir','folder','children','breadcrumb','search_term','search_recursive');

        return view("alfresco::index",$params);
    }



    public function index(Request $request)
    {
        //$basepath='51364f1c-0f82-4567-ae86-98fce5e42c27';
    	if($request->input("path")){
        	$folder=Alfresco::getFolderByPath($request->input("path"));
        }else if($request->input("id")){
        	$folder=Alfresco::getFolder($request->input("id"));
        }else{
        	$folder=Alfresco::getBaseFolder();
        }
		$children=$folder->getChildren();
        return $children;

    }

    public function download($id)
    {
        
        Alfresco::downloadObject($id);

    }

    public function delete($id)
    {
        $obj=Alfresco::getObject($id);
        $path=$obj->getParent()->path;
        $path=($obj->getParent()->isBaseFolder())?'':$obj->getParent()->path;
        
        

        $ret=Alfresco::delete($id);

        if($ret){
            return redirect()
                    ->route('alfresco.show',[$path])
                    ->with(['success'=>"esborrat correctament"]);
        }else{
             return redirect()
                    ->route('alfresco.show',[$path])
                    ->with(['error'=>"No s'ha pogut esborrar"]);
        }


    }

    public function view($id)
    {
        Alfresco::downloadObject($id,true);

    }

    public function info($id)
    {
        $object=Alfresco::getObject($id);
        //dd($object);
        $attributes=get_object_vars($object);
        ksort($attributes);

        $breadcrumb=$this->makeBreadcrumb($object);
        $params=compact("object","breadcrumb",'attributes');

        return view("alfresco::info",$params);
    }

    public function addmodal($id){
        $folder=Alfresco::getObject($id);
        $params=compact('folder');

        return view("alfresco::modal-add",$params);
    }

    public function add($id, Request $request){
        //dd($request->all());
       
        $documents = $request->file('documents');
        $folder=Alfresco::getObject($id);

        
        $path=($folder->isBaseFolder())?'':$folder->path;
        $docs=$request->documents;

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
           
            $return=redirect()->route('alfresco.show',[$path]);

            if($ok) $return->with('success', implode("<br/>", $ok));
            if($err) $return->with('error', implode("<br/>", $err));

            return $return;


        }else{
             return redirect()
                    ->route('alfresco.show',[$path])
                    ->with(['error'=>"No s'han pogut pujar els arxius"]);
        }

        
    }


    public function createfoldermodal($id){
        $folder=Alfresco::getObject($id);
        $params=compact('folder');

        return view("alfresco::modal-createfolder",$params);
    }

    public function createfolder($id, Request $request){
        //dd($request->all());
        $name = $request->name;
        $parent=Alfresco::getObject($id);
        $path=($parent->isBaseFolder())?'':$parent->path;

        try{
            $folder=$parent->createFolder($name);
           

            if($folder){
                return redirect()
                    ->route('alfresco.show',[$path])
                    ->with(['success'=>"carpeta creada correctament"]);
            }else{
                 return redirect()
                    ->route('alfresco.show',[$path])
                    ->with(['error'=>"No s'ha pogut crear la carpeta"]);
            }
        }catch(Exception $e){
            //dd($e);
             return redirect()
                ->route('alfresco.show',[$path])
                ->with(['error'=>"Ja existeix una carpeta amb el mateix nom"]);
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

        $breadcrumb=$this->makeBreadcrumb($folder,__("Resultats de la cerca: :term",["term"=>$search_term]));
        //dd($breadcrumb);

        $results=Alfresco::search($search_term, $search_parent_id, $search_recursive);
       // dd($results);

        $sortfield=isset($request->sort)?$request->sort:"NAME";
        $sortdir=isset($request->direction)?$request->direction:"ASC";

        $results=AlfrescoHelper::sort($results,$sortfield,$sortdir);

       // dd($results);

        $params=compact('folder','breadcrumb','search_term','search_recursive','results');

        return view("alfresco::searchresults",$params);
    }

}