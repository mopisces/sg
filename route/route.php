<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

//生管专用
Route::group(':version/sg/',function(){
	Route::post('login','.LoginController/login');
	Route::post('factoryName','.LoginController/getFactoryName');
})->prefix('sg/:version');

Route::group(':version/sg/',function(){

	Route::post('alertGetValue','.AlterController/getValue');
	Route::post('alertChangeValue','.AlterController/changeValue');
	//Route::post('alertGetRecord','.AlterController/getRecord');
	//Route::post('alertClearRecord','.AlterController/clearRecord');

	Route::get('selectConfig','.SelectController/getConfig');
	Route::post('selectGetBl','.SelectController/getBl');
	Route::post('selectGetScdd','.SelectController/getScdd');
	Route::get('selectGetWgddConfig','.SelectController/getWgddConfig');
	Route::post('selectGetWgdd','.SelectController/getWgdd');
	Route::post('selectBlms','.SelectController/getBlms');

	Route::post('userList','.UserController/fetchList');
	Route::post('userDoEdit','.UserController/doEdit');
	Route::post('userDoStatus','.UserController/doStatus');

	Route::post('statis/getConfig','.StatisController/getStatisConfig');
	Route::post('statis/getStatisData', '.StatisController/getStatisData');
})->prefix('sg/:version')->middleware(['CheckSg']);

Route::group(':version/sg/',function(){
	Route::get('detectApi','.HideController/detect');
})->prefix('sg/:version');

Route::group('sg/',function(){
	Route::get('dataV/choose','Index/choose');
	Route::post('dataV/fetchData','Index/fetchData');
})->prefix('sg/');