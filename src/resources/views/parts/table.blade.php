<table class="table table-hover table-sm" id="alfresco-table" data-selectable="true" data-select-style="info">
	<thead >
		<tr>
			{{-- <th class="no-sort" data-order="desc" data-order-priority="1">&nbsp;</th> --}}
			<th>
				@button(['id'=>'checkall-btn','size'=>'xs','style'=>'light']) 
					@icon('check-double') 
				@endbutton
			</th>
			<th>
				@sortablelink('NAME',__('Nom'))
				
			</th>
			<th>
				@sortablelink('SIZE',__('Mida'))
			</th>
			<th>
				@sortablelink('TYPE',__('Tipus'))
			</th>
			<th>
				@sortablelink('UPDATEDBY',__('Usuari'))
			</th>
			<th>
				@sortablelink('UPDATED',__('Data'))
			</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		@foreach($children as $child)
			@if($child->isFile())
				@include('alfresco::parts.file-row',['file'=>$child])
			@else
				@include('alfresco::parts.folder-row',['folder'=>$child])
			@endif
		@endforeach
	</tbody>
</table>