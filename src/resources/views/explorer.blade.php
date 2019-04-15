@extends('ajtarragona-web-components::layout/master-sidebar')

@section('title')
	@lang('Alfresco Explorer')
@endsection



@section('breadcrumb')
    @breadcrumb([
    	'items'=> $breadcrumb
    ])

@endsection


@section('actions')
	
		@badge API: @badge(['type'=>'dark']) {{ strtoupper(config('alfresco.api'))}} @endbadge @endbadge
		@include('alfresco::parts.folder-actions')
		@include('alfresco::parts.table-actions')

		
	
@endsection


@section('body')
		{{-- @dump($breadcrumb) --}}

		{{-- @dump($folder) --}}

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
