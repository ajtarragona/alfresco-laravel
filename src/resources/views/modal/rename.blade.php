@extends('ajtarragona-web-components::layout/modal')

@section('id','modal-rename')

@section('title', 'Renombrar' )


@section('body')
	@form(['action'=>route('alfresco.rename',[$object->id]),'method'=>'post','autoselect'=>'name'])
		
		@input([
			'name'=>'name',
			'required'=>'true',
			'value' => $object->name,
			'placeholder'=>'Introdueix el nou nom'
		])
		


	<hr/>
	<div class="text-right">
		{{-- @button(['type'=>'button','class'=>'btn-sm','style'=>'light']) Cancel·lar @endbutton --}}
		@button(['type'=>'submit','class'=>'btn-sm','style'=>'secondary']) Guardar @endbutton
	</div>
	@endform

@endsection


