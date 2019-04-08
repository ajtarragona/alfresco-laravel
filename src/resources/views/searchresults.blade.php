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
	
@endsection


@section('body')
		{{-- @dump($breadcrumb) --}}

		{{-- @dump($folder) --}}
		@include('alfresco::_searchform')

		<p class="text-muted text-center">
			Resultats per la cerca '<strong>{{$search_term}}</strong>'' 
				@if($search_recursive) 
					a <strong>totes</strong> les carpetes {!! !$folder->isBaseFolder()?"a partir de <strong>".$folder->name."</strong>":"" !!}
				@else 
					a la carpeta <strong>{{ $folder->isBaseFolder()?"arrel":$folder->name }}</strong> 
				@endif
			: {{ $results?count($results):0}}
		</p>

		@if($results)
			
			@include("alfresco::_table",['children'=>$results])
		
		@endif
		

@endsection


@section('style')
	{{-- <link href="{{ asset('vendor/ajtarragona/css/accede.css') }}" rel="stylesheet"> --}}
@endsection


@section('js')
	{{-- <script src="{{ asset('vendor/ajtarragona/js/accede.js')}}" language="JavaScript"></script> --}}
@endsection
