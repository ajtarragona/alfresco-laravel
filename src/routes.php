<?php

Route::group(['prefix' => 'ajtarragona/alfresco','middleware' => ['web','auth','language']	], function () {

	
		
	Route::get('download/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@download')->name("alfresco.download");

	Route::get('view/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@viewDocument')->name("alfresco.view");
	
	Route::get('preview/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@previewDocument')->name("alfresco.preview");


	Route::delete('delete/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@delete')->name("alfresco.delete");
	

	Route::get('info/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@info')->name("alfresco.info");

	Route::get('add/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@addmodal')->name("alfresco.addmodal");

	Route::post('add/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@add')->name("alfresco.add");

	Route::get('createfolder/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@createfoldermodal')->name("alfresco.createfoldermodal");

	Route::post('createfolder/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@createfolder')->name("alfresco.createfolder");


	Route::get('rename/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@renamemodal')->name("alfresco.renamemodal");

	Route::post('rename/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@rename')->name("alfresco.rename");


	Route::post('search/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@search')->name("alfresco.search");
	
	Route::get('searchresults', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@searchresults')->name("alfresco.searchresults");

	Route::post('batch/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@batch')->name("alfresco.batch");

	Route::post('copy/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@copymodal')->name("alfresco.copymodal");

	Route::post('move/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@movemodal')->name("alfresco.movemodal");


	Route::get('tree/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@tree')->name("alfresco.tree");

	
	
});

Route::group(['prefix' => 'ajtarragona/alfresco','middleware' => ['alfresco-explorer','web','auth','language']	], function () {

		Route::get('/explorer/{folder?}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@explorer')->name("alfresco.explorer")->where('folder', '(.*)');
});