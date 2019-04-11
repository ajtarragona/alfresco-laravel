<tr>
	
	<td width="1%">
		@checkbox(['class'=>"tablecheck",'color'=>'info','name'=>"fileselected", 'value'=> $folder->id])
	</td>
	<td width="40%">
		<a href="{{ route('alfresco.explorer',[$folder->path]) }}" class="text-dark  text-decoration-none">
			{!! $folder->renderIcon() !!}{{ $folder->name }}
		</a>
	</td>
	<td width="10%"></td>
	<td width="20%"></td>
	<td width="10%"><span class="text-muted">{{ $folder->updatedBy }}</span></td>
	<td width="10%"></td>
	<td class="text-right row-actions">
		@buttongroup
			<a class="btn btn-xs btn-info" href="{{ route('alfresco.info',[$folder->id]) }}">
				@icon("info-circle")
			</a>
			@modalopener([
				'href'=>route('alfresco.renamemodal',[$folder->id]),
				'style' => 'light',
				'class' =>'btn btn-secondary btn-xs'
			])
				@icon("font")
			@endmodalopener
			
			<a class="btn btn-xs btn-secondary" href="{{ route('alfresco.download',[$folder->id]) }}">
				@icon("download")
			</a>
			@form(['class'=>'form-inline','action'=>route('alfresco.delete',[$folder->id]),'method'=>'delete', 'confirm'=> "Estàs segur?"])

				@button(['class'=>"btn-xs btn-danger", 'type'=>"submit"])
					@icon("trash")
				@endbutton

			@endform
		@endbuttongroup			
	</td>

</tr>