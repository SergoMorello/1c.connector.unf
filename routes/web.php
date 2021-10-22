<?php
route::get("/","main@index")->name('home');

route::group(['prefix'=>'order','middleware'=>'auth'],function() {

	route::post("/create","orderController@create");

});



route::group(['prefix'=>'test'],function(){
	
	route::get("/class/{id}","main@test");
	
	route::get("/",function() {
		controller::model('files');
		controller::model('def');
		//dd(['key'=>['value','value2']]);
		dd(controller::model());
		//dd(cache::get('test'));
		//return dd(file_get_contents('php://input'));
		//fdf
		//dd(asset('inc/style.css'));
		//dd(http::get('http://1c_api.ru/test/json/',['ttt'=>123,'fdff'=>123]));
		//cache::put('test','asdfdf',16);
		// cache::put('test2','fd d fefd');
		//cache::put('test3','fdf');
		//dd(cache::get('test'));
		return View('test.file',['name'=>md5(time())]);
	})->name('file');
	
	route::post("/submit",function() {
		request()->validate(['file'=>'file','name'=>'required']);
		request()->file('file')->storeAs('',request()->input('name').'.png');
		redirect()->route('file');
	})->name('submit');
	route::post("/post",function() {
		cache::put('test',file_get_contents('php://input'));
		return $_POST;
	});
	route::get("/json",function() {
		return request()->json();
	});
	
	route::get("/html",function() {
		return response('<h1>Hello World!!!</h1>');
	});
});
// route::group(['prefix'=>'api'],function() {
	// route::get("/test/{idf}/{ee}",function() {
		// return View('lay.qq');
	// })->name('test2');
// });
// route::get("/tt/{qw}/{ee?}","main@test")->name('test3');