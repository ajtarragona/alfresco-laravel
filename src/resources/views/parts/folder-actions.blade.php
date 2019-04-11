@if(isset($folder) && $folder)
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
@endif