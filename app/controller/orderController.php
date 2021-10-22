<?php
class orderController extends controller {
	
	public function __construct() {
		//cache::forget('ordersInSite');
	}
	
	public function create() {
		//return [123];
		// request::validate([
			// 'id'=>'required|number',
			// 'number'=>'required|number',
			// 'date'=>'required',
			// 'summ'=>'required',
			// 'items'=>'required|json',
			// 'client'=>'required|json'
		// ]);
		$request = request::json();
		
		$id = $request->id ?? 0;
		$date = $request->date ?? null;
		$adr = $request->adr ?? null;
		$des = $request->des ?? null;
		$delivery_type = $request->delivery_type ?? 1;
		
		$client = $request->client ?? [];
		$items = $request->items ?? [];
		
		if (!$id)
			return cache::get('ordersInSite');
		
		if (app()->ordersClass->add(
			$id,
			$date,
			$adr,
			$des,
			$delivery_type,
			$client,
			$items
		))
			return ['status'=>true];
		return ['status'=>false];
		
	}
	
	
	
	
	
	
	
}