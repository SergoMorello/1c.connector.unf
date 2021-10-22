<?php

class itemsClass extends connectClass {

	//Проверяем есть ли в ответе номенклатура
	public function itemsExists() {
		return isset(self::$items->return->Каталог->Товары->Товар);
	}
	
	//Получаем наименования для синхронизации
	public function getItems() {
		$ret = [];
		if (!isset(self::$items->return->Каталог->Товары->Товар))
			return $ret;
		$items = self::$items->return->Каталог->Товары->Товар;
		$items = is_array($items) ? $items : [$items];
		
		//Получаем массив свойств
		$getItemProps = function($item) {
			$ret = [];
			if (!isset($item->ЗначенияСвойств->ЗначенияСвойства))
				return $ret;
			$props = $item->ЗначенияСвойств->ЗначенияСвойства;
			$props = is_array($props) ? $props : [$props];
			
			//Получаем ID значения
			$getValueId = function($value, $parentId) {
				if (!isset(self::$items->return->Классификатор->Свойства->Свойство))
					return 0;
				$props = self::$items->return->Классификатор->Свойства->Свойство;
				$props = is_array($props) ? $props : [$props];
				foreach($props as $prop) {
					if ($parentId==$prop->Ид) {
						if (isset($prop->ВариантыЗначений->Справочник)) {
							$propValues = $prop->ВариантыЗначений->Справочник;
							$propValues = is_array($propValues) ? $propValues : [$propValues];
							foreach($propValues as $propValue) {
								if ($value==$propValue->Значение)
									return $propValue->ИдЗначения;
							}
						}
					}
				}
				return 0;
			};
			
			foreach($props as $prop) {
				if (!empty($prop->Значение))
					$ret[] = [
						'id'=>$getValueId($prop->Значение, $prop->Ид),
						'name'=>$prop->Значение
					];
			}
			return $ret;
		};
		
		foreach($items as $item) {
			$priceInf = $this->getInfItem(
									$item->Ид,
									$this->getTypePriceId(connectController::typePrice),
									$this->getTypePriceId(connectController::typePriceDiscount)
								);
			$ret[] = (object)[
				'id'=>$item->Ид,
				'group'=>($item->Группы->Ид ?? 0),
				'art'=>($item->Артикул ? $item->Артикул : 0),
				'code'=>$item->Код,
				'name'=>$item->ЗначенияРеквизитов->ЗначениеРеквизита[2]->Значение ?? $item->Наименование,
				'des'=>$item->Описание,
				'priceNum'=>$priceInf,
				'props'=>$getItemProps($item),
				'delete'=>((isset($item->Статус) && $item->Статус=='Удален') ? true : false)
			];
		}
		return $ret;
	}
	
	//Получаем цены и количества
	private function getInfItem($id, $idTypePrice, $idTypePriceDiscount) {
		if (!isset(self::$amountAndPrices->return->ПакетПредложений->Предложения->Предложение))
			return;
		$infos = self::$amountAndPrices->return->ПакетПредложений->Предложения->Предложение;
		$infos = is_array($infos) ? $infos : [$infos];
		foreach($infos as $info) {
			if ($info->Ид==$id) {
				$getPriceInf = function($id) use (&$info) {
					if (!isset($info->Цены->Цена))
						return;
					$prices = $info->Цены->Цена;
					$prices = is_array($prices) ? $prices : [$prices];
					foreach($prices as $i) {
						if ($i->ИдТипаЦены==$id) {
							return (object)[
								'price'=>$i->ЦенаЗаЕдиницу,
								'ed'=>$i->Единица,
								'curr'=>$i->Валюта,
								'ratio'=>$i->Коэффициент
							];
						}
					}
				};
				$prices = [];
				$prices[] = $getPriceInf($idTypePrice);
				$prices[] = $getPriceInf($idTypePriceDiscount);
				return (object)[
					'num'=>$info->Количество,
					'prices'=>$prices
				];
			}
		}
	}
	
	//Получаем id типа цены по его имени
	private function getTypePriceId($name) {
		if (!isset(self::$amountAndPrices->return->ПакетПредложений->ТипыЦен->ТипЦены))
			return;
		$typePrices = self::$amountAndPrices->return->ПакетПредложений->ТипыЦен->ТипЦены;
		$typePrices = is_array($typePrices) ? $typePrices : [$typePrices];
		foreach($typePrices as $tPrice)
			if ($tPrice->Наименование==$name)
				return $tPrice->Ид;
	}
}