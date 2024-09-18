@extends('alfresco::layout')

@section('title')
	@lang('Alfresco Explorer')
@endsection



@section('breadcrumb')
    @breadcrumb([
    	'items'=> $breadcrumb
    ])

@endsection


@section('actions')
	
		<span class="badge text-bg-dark">API: {{ strtoupper(config('alfresco.api'))}} </span>
		@include('alfresco::parts.folder-actions')
		@include('alfresco::parts.table-actions')

		
	
@endsection


@section('body')
		
		<div class="pt-3">
				
			@if($children)
				@include('alfresco::parts.searchform')
				@include("alfresco::parts.table",['children'=>$children])
			@else
					<p class="lead">@lang("El directori <strong>:name</strong> està buit",["name"=>$folder->name])</p>
					@if(!$folder->isBaseFolder())
						
						@button(['href'=> route('alfresco.explorer',[$folder->getParent()->path]),'style'=>'light','size'=>'sm'])
							@icon('angle-left') @lang("Tornar enrera")
						@endbutton
					@endif	
					

				
			@endif
		</div>

@endsection

@section('js')
	@include('alfresco::parts.tablescript')
@endsection

@section('css')
	@include('alfresco::parts.tablestyles')
@endsection
