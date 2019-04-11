@button(['style'=>'light','size'=>'sm','type'=>'button','id'=>'selected-actions','disabled'=>true])
	@icon('check-double') Arxius seleccionats @badge(['type'=>'dark','pill'=>true,'id'=>'selected-count']) @endbadge
	
	@slot('dropdown')

		@form(['action'=>route('alfresco.copymodal',[$folder->id]),'method'=>'POST' ,'id'=>'copy-form','target'=>'modal'])
			<input type="hidden" name="selected" class="selected-items" value=""/>
			<button type="submit" class="dropdown-item">@icon('copy') Copiar</button>
		@endform


		@form(['action'=>route('alfresco.movemodal',[$folder->id]),'method'=>'POST' ,'id'=>'move-form','target'=>'modal'])
			<input type="hidden" name="selected" class="selected-items" value=""/>
			<button type="submit" class="dropdown-item">@icon('paste') Moure</button>
		@endform


		@form(['action'=>route('alfresco.batch',[$folder->id]),'method'=>'POST' ,'id'=>'selected-form','confirm'=>"S'esborraran els arxius seleccionats. N'estàs segur?"])
			<input type="hidden" name="selected" class="selected-items" value=""/>
			
		    
		    <div class="dropdown-divider"></div>
		    <button type="submit" name="submitaction" value="delete" class="dropdown-item text-danger" href="#">@icon('trash') Esborrar</button>
	    @endform
	@endslot
@endbutton