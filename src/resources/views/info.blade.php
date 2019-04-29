@extends('ajtarragona-web-components::layout/master-sidebar')

@section('title')
	{{ $object->name }} 
@endsection



@section('breadcrumb')
    @breadcrumb([
    	'items'=> $breadcrumb
    ])
@endsection


@section('actions')
    @buttongroup
	    @button([
			'href'=>$object->downloadurl,
			'class' =>'btn-light btn-sm'
		])
			@icon('download') Descarregar
		@endbutton
		
		@if($object->isFile())
			@button([
				'href'=>$object->viewurl,
				'class' =>'btn-light btn-sm'
			])
				@icon('eye') Veure
			@endbutton
		@endif

		@form(['class'=>'form-inline','action'=>route('alfresco.delete',[$object->id]),'method'=>'delete', 'confirm'=> "Estàs segur?"])

			@button(['class'=>"btn-sm btn-danger", 'type'=>"submit"])
				@icon("trash") Esborrar
			@endbutton

		@endform
	@endbuttongroup
@endsection


@section('body')
	<div class="pt-3">
		<p class="lead">{!! $object->renderIcon() !!}{{ $object->name }}</p>
	
			@button(['href'=> route('alfresco.explorer',[$object->isDocument()?$object->getParent()->path:$object->path]),'style'=>'light','size'=>'sm'])
				@icon('angle-left') @lang("Tornar enrera")
			@endbutton

		@row(['class'=>'mt-3'])
			@col(['size'=>6])
				{{-- @dump($object->properties) --}}
				
				<table class="table table-bordered table-sm" >
					@foreach($attributes as $name=>$value)
					<tr>
						<th>{{ $name }}</th>
						<td>{!! $value !!}</td>
					</tr>
					@endforeach
				</table>
			@endcol

			@if($object->isImage())
				@col(['size'=>6])
					<figure>
						<img src="{{$object->viewurl}}" class="img-fluid"/>
					</figure>
				@endcol

			@elseif($object->hasPreview())
				@col(['size'=>6])
					<iframe src="{{$object->viewurl}}" class="previewiframe"></iframe>
				@endcol
			@endif

		@endrow
	</div>
@endsection


@section('css')
	<style>
		.previewiframe{
			width:100%;
			height:800px;
			border: 1px solid rgba(52, 58, 64, 0.08);
			background-color:white;
		}
	</style>
@endsection


