@form([
	'action'=>route('alfresco.search',[$folder->id]),
	'method'=>'post',
	'class'=>'mb-3'
	])
	
	
	{{-- @inputgroup	 --}}

		@input([
			'name'=>'term',
			'placeholder'=>'Introdueix la cerca',
			'icon'=>'search',
			'containerclass'=>'mb-0',
			'value'=>''

		])


	 {{--  <div class="input-group-append">
		@button(['type'=>'submit','class'=>'btn-sm','style'=>'secondary']) Buscar @endbutton
	  </div>

	  <div class="input-group-append ">
	  	<div class="p-2">

	  		@checkbox(['label'=>'Recursiu','name'=>'recursive','checked'=>false])
	  	</div>

	  </div> --}}

	{{-- @endinputgroup --}}
@endform
