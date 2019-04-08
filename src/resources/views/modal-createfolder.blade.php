@extends('ajtarragona-web-components::layout/modal')

@section('id','modal-create-folder')

@section('title', 'Crear carpeta' )


@section('body')
	@form(['action'=>route('alfresco.createfolder',[$folder->id]),'method'=>'post'])
		
		@input([
			'name'=>'name',
			'required'=>'true',
			'placeholder'=>'Introdueix el nom de la carpeta'
		])
		


	<hr/>
	<div class="text-right">
		{{-- @button(['type'=>'button','class'=>'btn-sm','style'=>'light']) Cancel·lar @endbutton --}}
		@button(['type'=>'submit','class'=>'btn-sm','style'=>'secondary']) Guardar @endbutton
	</div>
	@endform

@endsection


