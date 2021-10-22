<?php

class connectClass {
	private $client, $params;
	
	protected static $items, $amountAndPrices, $orders;
	
	//Получаем обьект клиента
	public function client() {
		return $this->client;
	}
	
	//Получаем наименования
	public function items(...$params) {
		return self::$items = $this->client->GetItems($this->params($params));
	}
	
	//Получаем количество и цены
	public function amountAndPrices(...$params) {
		return self::$amountAndPrices = $this->client->GetAmountAndPrices($this->params($params));
	}
	
	//Загружаем заказы
	public function LoadOrders($data) {
		if (is_array($data))
			return self::$orders = $this->client->LoadOrders($data);
	}
	
	//Получаем картинку товара
	public function GetPicture($id) {
		if (is_string($id))
			return $this->client->GetPicture(['ItemID'=>$id])->return;
	}
	
	//Подключаемся к серверу
	public function connect(...$params) {
		ini_set("soap.wsdl_cache_enabled", "0");
		
		if ($this->client = new SoapClient(connectController::url,
			   array("login" => connectController::login,
				 "password" => connectController::password,
				 "exceptions" => 0,
				 'keep_alive' => false))) {
		
			$this->params($params);
			
			return true;
		}
		return false;
	}
	
	private function params($params) {
		if (!is_array($params))
			return;
		if (count($params)==0)
			return $this->params;
		$this->params['ModificationDate'] = $params[0] ?? null;
		$this->params['GroupCode'] = $params[1] ?? null;
		return $this->params;
	}
}