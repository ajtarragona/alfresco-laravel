<?php

Route::group(['prefix' => 'ajtarragona/alfresco','middleware' => ['web','auth','language']	], function () {

	
	
	//Route::get('test', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@index');
	
	Route::get('download/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@download')->name("alfresco.download");

	Route::delete('delete/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@delete')->name("alfresco.delete");
	
	Route::get('view/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@view')->name("alfresco.view");

	Route::get('info/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@info')->name("alfresco.info");

	Route::get('add/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@addmodal')->name("alfresco.addmodal");

	Route::post('add/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@add')->name("alfresco.add");

	Route::get('createfolder/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@createfoldermodal')->name("alfresco.createfoldermodal");

	Route::post('createfolder/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@createfolder')->name("alfresco.createfolder");


	Route::post('search/{id}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@search')->name("alfresco.search");
	
	Route::get('searchresults', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@searchresults')->name("alfresco.searchresults");



	Route::get('/go/{folder?}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@show')->name("alfresco.show")->where('folder', '(.*)');
});