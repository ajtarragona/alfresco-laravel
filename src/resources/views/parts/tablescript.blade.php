<script language="JavaScript">
	

	$(document).ready(function(){
		
		$('body').on('click','.folders-tree .folder .opener',function(e){
			var li=$(this).closest('.folder');
			al("click");
			al(li);

			if(!li.is('.has-children')) return;
			
			e.preventDefault();
			e.stopPropagation();

			if(li.is('.loaded')){
				li.find(">ul").toggle();
				li.find('>.folder-node >.opener >i').toggleClass('fa-folder-open').toggleClass('fa-folder');
			
			}else{
				
				var id=li.data('id');
				li.startLoading();
				$.get(route('alfresco.tree',{id:id, currentFolderId: '{{$folder->id}}' }), function(data){
					li.addClass("loaded");
					li.find('>.folder-node >.opener >i').addClass('fa-folder-open').removeClass('fa-folder');
					li.append(data);
					li.stopLoading();
				});
			}
		});

		$('body').on('click','.folders-tree .folder-node',function(e){
			e.preventDefault();
			e.stopPropagation();
			var li=$(this).closest("li");

			if(li.is('.disabled')) return;

			li.closest('.folders-tree').find('.folder').removeClass('active');
			li.closest('.folders-tree').find('.folder .opener i').removeClass('text-primary').addClass('text-info');
			li.addClass('active');
			li.find('>.folder-node >.opener >i').addClass('text-primary').removeClass('text-info');
			li.closest('form').find('[name=folderId]').val(li.data('id'));
		});




		$("#alfresco-table").on('tgntable:ready',function(e){
			al("alfresco-table ready");
			var table=this;

			this.$table=$(this);
			this.$count=$('#selected-count');
			this.$formfields=$('input.selected-items');
			this.$tbody=$(this).find("tbody");
			this.$checkboxes=this.$tbody.find("[type=checkbox]");
			this.$actionsbtn=$("#selected-actions");
			this.$checkallbtn=$("#checkall-btn");

			this.toggleSelectAll = function(){
				if(this.allSelected()){
			 		this.deselectAll();
			 	}else{
			 		this.selectAll();
			 	}
			}
			 
			this.allSelected = function(){
			 	return this.$table.tgnTable('allSelected');
			}

			this.hasSelected = function(){
			 	return this.$table.tgnTable('hasSelected');
			}

			this.selectAll = function(){
			 	return this.$table.tgnTable('selectAll');
			}

			this.deselectAll = function(){
			 	return this.$table.tgnTable('deselectAll');
			}


			this.getSelectedIds = function(){
			 	var selectedRows=this.$table.tgnTable('getSelected');
			 	//al(selectedRows);
			 	var ret=[];
				
				selectedRows.each(function(){
					ret.push($(this).find("[type=checkbox]").val());
				});
				

				return ret;
			}

			 

			this.updateForm = function(){
			 	if(this.hasSelected()){
					this.$actionsbtn.prop('disabled',false);
				}else{
					this.$actionsbtn.prop('disabled',true);
				}

			 	var val=this.getSelectedIds();
			 	this.$formfields.val(JSON.stringify(val));
			 	//al(val);
			 	this.$count.html(val.length);
			}


			this.$checkboxes.on('change',function(){
				table.updateForm();
			});
			
			this.$checkallbtn.on('click',function(){
				table.toggleSelectAll();
			});

			this.updateForm();

		});	 
		
		
	});
</script>