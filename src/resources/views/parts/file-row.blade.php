<tr>
	<td width="1%">
		@checkbox(['class'=>"tablecheck",'color'=>'info','name'=>"fileselected", 'value'=> $file->id])
	</td>
	<td width="40%">
		<a href="{{ route('alfresco.info',[$file->id]) }}" class="text-dark text-decoration-none">
			{!! $file->renderIcon() !!}{{ $file->name }}
		</a>
	</td>
	<td width="10%"  > {{ $file->humansize }}</td>
	<td width="20%" ><span class="text-muted"  title="{{ $file->mimetype }}">{{ $file->mimetypedescription }}</span></td>
	<td width="10%"><span class="text-muted">{{ $folder->updatedBy }}</span></td>
	<td width="10%"><span class="text-muted text-nowrap">{{ datetimeformat(_date($file->updated)) }}</span></td>
	<td class="text-right row-actions">
		@buttongroup
			{{-- 	<a class="btn btn-xs btn-info" href="{{ route('alfresco.info',[$file->id]) }}">
				@icon("info-circle")
			</a> --}}

			<a class="btn btn-xs btn-secondary" href="{{ route('alfresco.download',[$file->id]) }}">
				@icon("download")
				
			</a>

			@modalopener([
				'href'=>route('alfresco.renamemodal',[$file->id]),
				'style' => 'light',
				'class' =>'btn btn-secondary btn-xs'
			])
				@icon("font")
			@endmodalopener
			
			@form(['class'=>'form-inline','action'=>route('alfresco.delete',[$file->id]),'method'=>'delete', 'confirm'=> "Estàs segur?"])

				@button(['class'=>"btn-xs btn-danger", 'type'=>"submit"])
					@icon("trash")
				@endbutton

			@endform
		@endbuttongroup
	</td>
</tr>