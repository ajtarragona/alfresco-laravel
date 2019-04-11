@extends('ajtarragona-web-components::layout/modal')

@section('id','modal-move-files')

@section('title', 'Moure a' )


@section('body')
	@form(['action'=>route('alfresco.batch',[$folder->id]),'method'=>'post'])
		<input type="hidden" name="submitaction" value="move"/>
		<input type="hidden" name="selected" value="{{ json_encode($selected) }}"/>
		<input type="hidden" name="folderId" value=""/>

		<div class="folders-tree">
			@include('alfresco::parts.tree')
		</div>


	<hr/>
	<div class="text-right">
		{{-- @button(['type'=>'button','class'=>'btn-sm','style'=>'light']) Cancel·lar @endbutton --}}
		@button(['type'=>'submit','class'=>'btn-sm','style'=>'secondary']) Moure arxius @endbutton
	</div>
	@endform

@endsection


