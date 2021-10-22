<?php

class propsClass extends connectClass {
	
	public function getProps() {
		$ret = [];
		if (!isset(self::$items->return->Классификатор->Свойства->Свойство))
			return $ret;
		$props = self::$items->return->Классификатор->Свойства->Свойство;
		$props = is_array($props) ? $props : [$props];
		
		$recProps = function($props) use (&$ret) {
			$props = is_array($props) ? $props : [$props];
			foreach($props as $prop) {
				$ret[] = (object)[
					'id'=>$prop->Ид,
					'parent'=>0,
					'name'=>$prop->Наименование
				];
				if (isset($prop->ВариантыЗначений->Справочник)) {
					$propValues = $prop->ВариантыЗначений->Справочник;
					$propValues = is_array($propValues) ? $propValues : [$propValues];
					foreach($propValues as $propValue) {
						$ret[] = (object)[
							'id'=>$propValue->ИдЗначения,
							'parent'=>$prop->Ид,
							'name'=>$propValue->Значение
						];
					}
				}
			}
		};
		
		$recProps($props);
		
		return $ret;
	}
	
	//Получаем имя свойства
	public function getPropName($id) {
		if (!isset(self::$items->return->Классификатор->Свойства->Свойство))
			return;
		$props = self::$items->return->Классификатор->Свойства->Свойство;
		foreach($props as $prop)
			if ($prop->Ид==$id)
				return $prop->Наименование;
	}
	
	//Получаем свойство по id значения
	public function getParentProp($propId) {
		if (!isset(self::$items->return->Классификатор->Свойства->Свойство))
			return;
		$props = self::$items->return->Классификатор->Свойства->Свойство;
		foreach($props as $prop) {
			if ($prop->ВариантыЗначений->Справочник->ИдЗначения==$propId)
				return (object)[
					'id'=>$prop->Ид,
					'name'=>$prop->Наименование
				];
		}
	}
}