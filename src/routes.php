<?php

Route::group(['prefix' => 'ajtarragona/alfresco','middleware' => ['web','auth','language']	], function () {
	Route::get('download/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@download', 'as' => 'alfresco.download']);
	Route::get('view/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@viewDocument', 'as' => 'alfresco.view']);
	Route::get('preview/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@previewDocument', 'as' => 'alfresco.preview']);
	Route::delete('delete/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@delete', 'as' => 'alfresco.delete']);
	Route::get('info/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@info', 'as' => 'alfresco.info']);
	Route::get('add/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@addmodal', 'as' => 'alfresco.addmodal']);
	Route::post('add/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@add', 'as' => 'alfresco.add']);
	Route::get('createfolder/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@createfoldermodal', 'as' => 'alfresco.createfoldermodal']);
	Route::post('createfolder/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@createfolder', 'as' => 'alfresco.createfolder']);
	Route::get('rename/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@renamemodal', 'as' => 'alfresco.renamemodal']);
	Route::post('rename/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@rename', 'as' => 'alfresco.rename']);
	Route::post('search/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@search', 'as' => 'alfresco.search']);
	Route::get('searchresults', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@searchresults', 'as' => 'alfresco.searchresults']);
	Route::post('batch/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@batch', 'as' => 'alfresco.batch']);
	Route::post('copy/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@copymodal', 'as' => 'alfresco.copymodal']);
	Route::post('move/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@movemodal', 'as' => 'alfresco.movemodal']);
	Route::get('tree/{id}', ['uses' => 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@tree', 'as' => 'alfresco.tree']);
});

Route::group(['prefix' => 'ajtarragona/alfresco','middleware' => ['alfresco-explorer','web','auth','language']	], function () {
	Route::get('/explorer/{folder?:(.*)}', 'Ajtarragona\AlfrescoLaravel\Controllers\AlfrescoLaravelController@explorer');
});
