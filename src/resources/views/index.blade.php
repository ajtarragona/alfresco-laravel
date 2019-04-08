@extends('ajtarragona-web-components::layout/master-sidebar')

@section('title')
	@lang('Alfresco')
@endsection



@section('breadcrumb')
    @breadcrumb([
    	'items'=> $breadcrumb
    ])
@endsection


@section('actions')
	<a class="btn btn-sm btn-info" href="{{ route('alfresco.info',[$folder->id]) }}">
		@icon("info-circle") Metadata
	</a>

	@modalopener([
		'href'=>route('alfresco.createfoldermodal',[$folder->id]),
		'icon' => 'folder-plus',
		'style' => 'light',
		'class' =>'btn btn-light btn-sm'
	])
		Crear carpeta
	@endmodalopener

    @modalopener([
		'href'=>route('alfresco.addmodal',[$folder->id]),
		'icon' => 'upload',
		'style' => 'light',
		'class' =>'btn btn-light btn-sm'
	])
		Afegir arxius
	@endmodalopener
@endsection


@section('body')
		{{-- @dump($breadcrumb) --}}

		{{-- @dump($folder) --}}
		@include('alfresco::_searchform')

		
		@if($children)
			@include("alfresco::_table",['children'=>$children])
		@endif
		

@endsection


@section('style')
	{{-- <link href="{{ asset('vendor/ajtarragona/css/accede.css') }}" rel="stylesheet"> --}}
@endsection


@section('js')
	{{-- <script src="{{ asset('vendor/ajtarragona/js/accede.js')}}" language="JavaScript"></script> --}}
@endsection
