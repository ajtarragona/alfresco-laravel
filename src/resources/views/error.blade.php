@extends('ajtarragona-web-components::layout/master')

@section('title')
	@lang('Alfresco Error')
@endsection



@section('body')
		@container(['fluid'=>false])
			@alert(['type'=>'danger','class'=>'mt-5'])
				{!! $error !!}
			@endalert
		@endcontainer

@endsection
