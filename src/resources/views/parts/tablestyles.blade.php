<style>
	.folders-tree ul{
		margin:0;
		padding:0 0 0 .6em;
		list-style: none;
	}
	.folders-tree li {
		margin:0;
		padding:0;
	}

	.folders-tree li .folder-node{
		display:block;
		padding:.3em 0;
	}
	
	.folders-tree li.active >.folder-node >.folder-text{
		font-weight:bold;
	}
	.folders-tree .folder-text{
		cursor:pointer;
	}

	.folders-tree li.disabled >.folder-node > .folder-text {
		opacity:.6;
		text-decoration: line-through;
	}


	#alfresco-table tbody tr td.row-actions .btn-group{
		opacity:0;
	}
	
	#alfresco-table tbody tr:hover td.row-actions .btn-group{
		opacity:1;
	}

</style>