@extends('ajtarragona-web-components::layout/master-sidebar')

@section('title')
	@lang('Alfresco search results')
@endsection



@section('breadcrumb')
    @breadcrumb([
    	'items'=> $breadcrumb
    ])
@endsection


@section('actions')
	@include('alfresco::parts.table-actions')

@endsection


@section('body')
		{{-- @dump($breadcrumb) --}}

		{{-- @dump($folder) --}}
		@include('alfresco::parts.searchform')

		<p class="text-muted text-center">
			Resultats per la cerca '<strong>{{$search_term}}</strong>'' 
				@if($search_recursive) 
					a <strong>totes</strong> les carpetes {!! !$folder->isBaseFolder()?"a partir de <strong>".$folder->name."</strong>":"" !!}
				@else 
					a la carpeta <strong>{{ $folder->isBaseFolder()?"arrel":$folder->path }}</strong> 
				@endif
			: @badge(['pill'=>true,'type'=>'dark']) {{ $results?count($results):0}} @endbadge
		</p>

		@if($results)
			
			@include("alfresco::parts.table",['children'=>$results])
		
		@endif
		

@endsection

@section('js')
	@include('alfresco::parts.tablescript')
@endsection

@section('css')
	@include('alfresco::parts.tablestyles')
@endsection
