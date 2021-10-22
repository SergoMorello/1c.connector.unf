<?php

class catalogClass extends connectClass {
	
	public function getCatalog() {
		$ret = [];
		if (!isset(self::$items->return->Классификатор->Группы->Группа->Группы->Группа))
			return $ret;
		$catalog = self::$items->return->Классификатор->Группы->Группа->Группы->Группа;
		
		$catalog = is_array($catalog) ? $catalog : [$catalog];
		
		$recGroups = function($groups, $parentId) use (&$ret, &$recGroups) {
			$groups = is_array($groups) ? $groups : [$groups];
			foreach($groups as $cat) {
					$ret[] = (object)[
						'id'=>$cat->Ид,
						'parent'=>$parentId,
						'name'=>$cat->Наименование
					];
				if (isset($cat->Группы->Группа))
					$recGroups($cat->Группы->Группа, $cat->Ид);
			}
		};
		
		$recGroups($catalog, 0);
		
		return $ret;
	}
}