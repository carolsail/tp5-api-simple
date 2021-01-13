<?php
Route::group('', function(){
	Route::get('hello/:name', 'index/hello');
})->allowCrossDomain();