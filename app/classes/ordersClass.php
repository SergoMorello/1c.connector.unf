<?php

class ordersClass {
	
	private static $orders = [];
	
	public function __construct() {
		if (cache::has('ordersInSite'))
			self::$orders = cache::get('ordersInSite');
		else
			cache::put('ordersInSite',self::$orders);
	}
	
	public function syn() {
		
	}
	
	public function get() {
		return self::$orders ?? [];
	}
	
	public function add($id, $date, $adr, $des, $delivery_type, $client, $items) {
		if ($this->checkExists($id)>=0)
			return;
		
		$summ = 0;
		
		if (is_array($items))
			foreach($items as $item)
				$summ += $item->price*$item->num;
		
		self::$orders[] = [
			'id'=>$id,
			'date'=>$date,
			'adr'=>$adr,
			'des'=>$des,
			'delivery_type'=>$delivery_type,
			'summ'=>$summ,
			'client'=>$client,
			'items'=>$items
		];
		$this->update();
		return true;
	}
	
	public function send($data) {
		
		$dateSr = explode(' ',$data['date']->date);
		$date = $dateSr[0];
		$time = explode('.',$dateSr[1])[0];
		
		$items = [];
		if (count($data['items'])>0)
			foreach($data['items'] as $item) {
				$items[] = Array(
						"Ид" => $item->id_1c,
						"Артикул" => $item->art,
						"Наименование" => $item->name,
						"БазоваяЕдиница" => Array(
							"Код" => "796",
							"НаименованиеПолное" => "Штука",
							"МеждународноеСокращение" => "PCE"
						),
						"ЦенаЗаЕдиницу" => $item->price,
						"Количество" => $item->num,
						"Резерв" => $item->num,
						"Сумма" => ($item->price*$item->num),
						"Единица" => "шт",
						"Коэффициент" => "1"
					);
			}
		
		$OrdersData = Array(
			"ВерсияСхемы" => "2.05",
			"ДатаФормирования" => $date."T".$time,
			"Документ" => Array(
				"Ид" => $data['id'],
				"Номер" => $data['id'],
				"Дата" => $date,
				"ХозОперация" => "Заказ товара",
				"Роль" => "Продавец",
				"Валюта" => "руб",
				"Курс" => "1",
				"Сумма" => $data['summ'],
				"Контрагенты" => Array(
					"Контрагент" => Array(
						"Ид" => null,
						"Наименование" => $data['client']->name,
						"ПолноеНаименование" => $data['client']->name,
						"Роль" => "Покупатель"
					)
				),
				"Время" => $time,
				"Товары" => Array(
					"Товар" => $items
				)
			)
		);
		if ($data['adr'])
			$OrdersData['Документ']['Контрагенты']['Контрагент']['Адрес'] = [
							"Представление" => $data['adr'],
							"Комментарий" => $data['des']
						];
		if ($data['des'])
			$OrdersData['Комментарий'] = $data['des'];
		
		
		$result = app()->connectClass->LoadOrders(['OrdersData'=>$OrdersData]);
		if (($result->return ?? false))
			return true;
		return false;
	}
	
	//Обновляем кэш
	public function update() {
		cache::put('ordersInSite',self::$orders);
	}
	
	public function ordersExists() {
		if (cache::has('ordersInSite'))
			self::$orders = cache::get('ordersInSite');
		return count(self::$orders)>0 ? true : false;
	}
	
	public function delete($id) {
		$key = $this->checkExists($id);
		if ($key>=0) {
			unset(self::$orders[$key]);
			$this->update();
		}
	}
	
	public function checkExists($id) {
		foreach(self::$orders as $key=>$order)
			if ($id==$order['id'])
				return $key;
		return -1;
	}
}