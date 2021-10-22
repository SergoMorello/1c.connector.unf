<?php
class main extends controller {
	public function index() {
		//$newTest = $this->model->tasklist->find('37');
		// $newTest->login = 'dfsef ауцавуыау';
		// $newTest->pass = md5('test');
		// dd($newTest->save());
		//session(['test'=>321]);
		//$newTest->login = 'test1331';
		//$newTest->email = 'test@test.ru';
		//$res = $this->model->tasklist->get();
		//$newTest->delete();
		// $res = $this->model->tasklist->select('login as wee')->whereIn(['id',[33,34,36]])->where('id',36);
		// dd($res->get());
		//redirect()->route('test2',[12,34]);
		//return View('test',['test'=>'teswtf']);
		//dd($this->model->tasklist->limit(30)->get());
		//return response()->json($this->model->tasklist->select('id')->limit(2)->count());
		//return View('test');
		//return response()->json([1]);
		
		return View('home');
	}
	public function test($id) {
		$this->model('files');
		dd($this->model()->files->get());
		return response()->json();
	}
}