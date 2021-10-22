<?php
class connectController extends controller {
	private $items, $amountAndPrices, $catalogInSite, $itemInSite, $propsInSite, $firstRun, $error, $date;
	
	const url = 'http://194.170.100.85/test/ws/SiteExchange2?wsdl';
	const site = 'http://127.0.0.1:8000/api/';
	const login = 'Администратор';
	const password = '';
	const groupId = 'НФ-00004846';
	//const groupId = null;
	const typePrice = 'Учетная цена';
	const typePriceDiscount = 'Цена со скидкой';
	
	
	private function http() {
		return http::withDigestAuth('admin','admin')->withRealm('oti8k1iqcy3NCaot');
	}
	
	
	public function syn() {
		log::info("Подключаемся к 1с...");
		
		app()->connectClass->connect();
		
		log::info("Проверяем изменения...");
		
		$this->firstRun = true;
		
		$this->error = false;
		
		$errorStep = 0;
		
		while(true) {
			$sleep = 1;
			
			$this->getCurentItems();
			
			if (app()->itemsClass->itemsExists())
				$this->error = $this->update() ? false : true;
			
			if (app()->ordersClass->ordersExists())
				$this->error = $this->orders() ? false : true;
			
			if ($this->error) {
				if ($errorStep>=5) {
					log::error("Не получается выполнить множество запросов, ждём...");
					$sleep = 180;
				}
				++$errorStep;
				$sleep = 60;
			}else{
				$errorStep = 0;
				$this->firstRun = false;
			}
			
			sleep($sleep);
		}
	}
	
	private function orders() {
		$orders = app()->ordersClass->get();
		$orderClass = app()->ordersClass;
		log::info('Обновляем заказы...');
		foreach($orders as $order) {
			$orderClass->send($order);
			$orderClass->delete($order['id']);
		}
		return true;
	}
	
	public function update() {
		
		//dd(http::withBasicAuth('Администратор','')->post('http://194.170.100.85/test/hs/get/items')->json());
		//dd(http::withDigestAuth('admin','admin')->withRealm('oti8k1iqcy3NCaot')->post('http://127.0.0.1:8000/api/item/create')->json());
		
		//dd($this->get1cDate());
		
		//$date = date("Y-m-d\TH:i:s", mktime(date('H'), date('i') - 5, date('s'), date('m'), date('d'), date('Y') - 3));
		
		// log::info("Подключаемся к 1с...");
		
		// app()->connectClass->connect($date, 'НФ-00004846');
		
		//dd(app()->connectClass->GetPicture('68c671f9-229f-11ec-80bd-a4bf012bf34e'));
		
		
		$this->getCurentItems(60);
		
		log::info("Получаем количество и цены...");
		
		$this->amountAndPrices = $this->error ? $this->amountAndPrices : app()->connectClass->amountAndPrices();

		log::info("Получаем данные с сайта");
		
		log::info("Каталоги...");
		
		//Кэшируем каталог сайта
		if (cache::has('catalogInSite'))
			$this->catalogInSite = cache::get('catalogInSite');
		else{
			
			$response = $this->http()->get(self::site.'catalog/list')->throw(function($r, $e){
				log::error('error get catalog list: '.$e['message'] ?? null);
			});
			if ($response->successful()) {
				$this->catalogInSite = $response->json();
				cache::put('catalogInSite',$this->catalogInSite);
			}else
				return false;
		}
		
		log::info("Номенклатура...");
		
		//Кэшируем наименования сайта
		if (cache::has('itemInSite'))
			$this->itemInSite = cache::get('itemInSite');
		else{
			
			$response = $this->http()->get(self::site.'item/list')->throw(function($r, $e){
				log::error('error get item list: '.$e['message'] ?? null);
			});
			if ($response->successful()) {
				$this->itemInSite = $response->json();
				cache::put('itemInSite',$this->itemInSite);
			}else
				return false;
		}
		
		
		log::info("Свойства номенклатуры...");
		
		//Кэшируем свойства наименований сайта
		if (cache::has('propsInSite'))
			$this->propsInSite = cache::get('propsInSite');
		else{

			$response = $this->http()->get(self::site.'props/list')->throw(function($r, $e){
				log::error('error get prop list: '.$e['message'] ?? null);
			});
			if ($response->successful()) {
				$this->propsInSite = $response->json();
				cache::put('propsInSite',$this->propsInSite);
			}else
				return false;
		}
		
		log::info("Синхронизируем");
		
		log::info("Каталоги...");
		
		if (!$this->synCatalog())
			return false;
		
		log::info("Свойства...");
		
		if (!$this->synProps())
			return false;
		
		log::info("Номенклатуру...");
		
		if (!$this->synItem())
			return false;
		
		if (!$this->deleteEmptyCats())
			return false;
		
		log::info("Готово");
		
		return true;
	}
	
	private function getCurentItems($seconds=0) {
		$this->date = ($this->error || $this->firstRun) ? $this->date : $this->get1cDate($seconds);
			
		$this->items = $this->error ? $this->items : app()->connectClass->items($this->date, self::groupId);
	}
	
	private function get1cDate($seconds=0) {
		
		$obj1cDate = function($date) {
			$dt_explode = explode('T',$date);

			list($year, $month, $day) = explode('-', $dt_explode[0]);

			list($hours, $minutes, $seconds) = explode(':',$dt_explode[1]);
			return (object)[
				'year'=>$year,
				'month'=>$month,
				'day'=>$day,
				'hours'=>$hours,
				'minutes'=>$minutes,
				'seconds'=>$seconds
			];
		};
		
		if (isset($this->items->return->ДатаФормирования)) {
			$return = $obj1cDate($this->items->return->ДатаФормирования);

			return date("Y-m-d\TH:i:s", mktime(
						$return->hours,
						$return->minutes,
						$return->seconds - ($seconds),
						$return->month,
						$return->day,
						$return->year
					));
		}else
			return null;
	}
	
	private function synProps() {
		$props = app()->propsClass->getProps();
		
		$numProps = count($props);
		
		$i = 0;
		
		foreach($props as $prop) {
			
			$return = null;
			
			if ($siteObj = $this->siteIdHash($prop->id, $this->propsInSite)) {
				
				$this->checkHash([
					'name'=>$prop->name,
					'des'=>'',
					'parent'=>$this->siteId($prop->parent, $this->propsInSite),
					'pos'=>0
				], 
				$siteObj->hash, 
				function($data) use (&$siteObj, &$return) {
					$response = $this->http()->post(self::site.'props/update/'.$siteObj->id, $data)->json();
					if (($response['success'] ?? false)) {
						$this->updateHash($siteObj->id, $data['hash'], $this->propsInSite);
						cache::put('propsInSite',$this->propsInSite);
					}else
						$return = $response;
				});
				
			}else{
				
				$this->checkHash([
					'id'=>$prop->id,
					'name'=>$prop->name,
					'des'=>'',
					'parent'=>$this->siteId($prop->parent, $this->propsInSite),
					'pos'=>0
				], 
				'', 
				function($data) use (&$prop, &$return) {
					$response = $this->http()->post(self::site.'props/create', $data)->json();
					if (($response['success'] ?? false)) {
						$this->propsInSite[] = [
							'id'=>$response['id'],
							'id_1c'=>$prop->id,
							'hash'=>$data['hash']
							];
						cache::put('propsInSite',$this->propsInSite);
					}else
						$return = $response;
				});
			}
			log::thisLine(true)->info(ceil($i*100/$numProps).'%');
			++$i;
			
			if (!is_null($return)) {
				log::error('Error: '.print_r($return, true));
				return false;
			}
		}
		return true;
	}

	
	private function synCatalog() {
		
		$catalog = app()->catalogClass->getCatalog();
		
		$numCat = count($catalog);
		
		$i = 0;
		
		foreach($catalog as $cat) {
			$return = null;
			
			$parent = $this->siteId($cat->parent, $this->catalogInSite);
			if ($siteObj = $this->siteIdHash($cat->id, $this->catalogInSite)) {
				
				$this->checkHash([
					'cid'=>$parent,
					'name'=>$cat->name,
					'des'=>''
				], 
				$siteObj->hash, 
				function($data) use (&$siteObj, &$return) {
					$response = $this->http()->post(self::site.'catalog/update/'.$siteObj->id, $data)->json();
					if (($response['success'] ?? false)) {
						$this->updateHash($siteObj->id, $data['hash'], $this->catalogInSite);
						cache::put('catalogInSite',$this->catalogInSite);
					}else
						$return = $response;
				});
				
			}else{
				
				$this->checkHash([
					'id'=>$cat->id,
					'cid'=>$parent,
					'name'=>$cat->name,
					'des'=>''
				], 
				'', 
				function($data) use (&$cat, &$return) {
					$response = $this->http()->post(self::site.'catalog/create', $data)->json();
					if (($response['success'] ?? false)) {
						$this->catalogInSite[] = [
							'id'=>$response['id'],
							'id_1c'=>$cat->id,
							'hash'=>$data['hash']
							];
						cache::put('catalogInSite',$this->catalogInSite);
					}else
						$return = $response;
				});
			}
			log::thisLine(true)->info(ceil($i*100/$numCat).'%');
			++$i;
			
			if (!is_null($return)) {
				log::error('Error: '.print_r($return, true));
				return false;
			}
		}
		return true;
	}
	
	private function deleteEmptyCats() {
		$response = $this->http()->get(self::site.'catalog/delete');
		if ($response->successful()) {
			$json = $response->json();
			if (($json['success'] ?? false))
				log::info("Удаляем пустые каталоги...");
			$this->siteIdDelete(($json['ids'] ?? []), $this->catalogInSite);
			return cache::put('catalogInSite', $this->catalogInSite);
		}else
			return false;
	}
	
	
	private function clearCacheSite() {
		cache::forget('catalogInSite');
		cache::forget('itemInSite');
	}
	
	//Получаем id обьекта на сайте по id 1c
	private function siteId($id, $list) {
		if (!is_array($list))
			return 0;

		if (is_string($id))
			foreach($list as $li)
				if ($id==$li['id_1c'])
					return $li['id'];
				
		return 0;
	}
	
	//Получаем id и хэш обьекта на сайте по id 1c
	private function siteIdHash($id, $list) {
		if (is_array($list) && is_string($id)) {
			foreach($list as $li)
				if ($id==$li['id_1c'])
					return (object)[
						'id'=>$li['id'],
						'hash'=>$li['hash'] ?? ''
						];
		}
	}
	
	private function siteIdDelete($ids, &$list) {
		if (!is_array($list))
			return;
		$ids = is_array($ids) ? $ids : [$ids];
		foreach($list as $key=>$li)
			if (in_array($li['id'], $ids))
				unset($list[$key]);
	}
	
	private function checkHash($data, $hash, $callback) {
		if (!is_array($data))
			return;
		$strHash = '';
		foreach($data as $dt)
			$strHash .= $dt;
		$newHash = md5($strHash);
		if ($newHash==$hash)
			return false;
		$data['hash'] = $newHash;
		$callback($data);
	}
	
	private function updateHash($id, $hash, &$data) {
		if (!is_array($data))
			return;
		foreach($data as $key=>$dt) {
			if ($id==$dt['id'])
				return $data[$key]['hash'] = $hash;
		}
	}
	
	private function synItem() {
		
		$propsId2string = function($props) {
			if (!is_array($props))
				return;
			$arr = [];
			foreach($props as $prop)
				$arr[] = $this->siteId($prop['id'],$this->propsInSite);
			
			return implode(',',$arr);
		};
		
		$items = app()->itemsClass->getItems();
		
		$numItems = count($items);
		
		$i = 0;
		
		foreach($items as $item) {
			
			$return = null;
			
			$siteId = $this->siteId($item->id, $this->itemInSite);
			
			if ($item->delete) {
				
				if ($siteId) {
					$response = $this->http()->get(self::site.'item/delete/'.$siteId)->json();

					if (($response['success'] ?? false)) {
						$this->siteIdDelete($siteId, $this->itemInSite);
						cache::put('itemInSite', $this->itemInSite);
					}
				}
			}else{
				
				$cid = $this->siteId($item->group, $this->catalogInSite);
				
				if ($siteObj = $this->siteIdHash($item->id, $this->itemInSite)) {
					
					$this->checkHash([
						'cid'=>$cid,
						'name'=>$item->name,
						'price'=>($item->priceNum->prices[0]->price ?? 0),
						'priceDiscount'=>($item->priceNum->prices[1]->price ?? 0),
						'num'=>($item->priceNum->num ?? 0),
						'des'=>$item->des,
						'art'=>$item->art,
						'props'=>$propsId2string($item->props)
					], 
					$siteObj->hash, 
					function($data) use (&$siteId, &$return) {
						$response = $this->http()->post(self::site.'item/update/'.$siteId, $data)->json();
						
						if (($response['success'] ?? false)) {
							$this->updateHash($siteId, $data['hash'], $this->itemInSite);
							cache::put('itemInSite',$this->itemInSite);
						}else
							$return = $response;
					});
					
				}else{
					
					$this->checkHash([
						'id'=>$item->id,
						'cid'=>$cid,
						'name'=>$item->name,
						'price'=>($item->priceNum->prices[0]->price ?? 0),
						'priceDiscount'=>($item->priceNum->prices[1]->price ?? 0),
						'num'=>($item->priceNum->num ?? 0),
						'des'=>$item->des,
						'art'=>$item->art,
						'props'=>$propsId2string($item->props)
					],
					'',
					function($data) use (&$item, &$return) {
						$response = $this->http()->post(self::site.'item/create', $data)->json();
						if (($response['success'] ?? false)) {
							$this->itemInSite[] = [
								'id'=>$response['id'],
								'id_1c'=>$item->id,
								'hash'=>$data['hash']
							];
							cache::put('itemInSite',$this->itemInSite);
						}else
							$return = $response;
					});
				}

			}
			log::thisLine(true)->info(ceil($i*100/$numItems).'%');
			++$i;
			
			if (!is_null($return)) {
				log::error('Error: '.print_r($return, true));
				return false;
			}
		}
		return true;
	}
	
}