@extends('ajtarragona-web-components::layout/modal')

@section('id','modal-add-files')

@section('title', 'Afegir arxius' )


@section('body')
	@form(['action'=>route('alfresco.add',[$folder->id]),'method'=>'post'])
		
		@fileinput([
			'name'=>'documents',
			'multiple'=>true,
			'placeholder'=>'Tria arxius...'
		])
		


	<hr/>
	<div class="text-right">
		{{-- @button(['type'=>'button','class'=>'btn-sm','style'=>'light']) Cancel·lar @endbutton --}}
		@button(['type'=>'submit','class'=>'btn-sm','style'=>'secondary']) Pujar arxius @endbutton
	</div>
	@endform

@endsection


