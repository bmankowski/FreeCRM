<?php

namespace App\Modules\Vtiger\UiTypes;

/**
 * UIType ReferenceProcess Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ReferenceProcess extends BaseUiType
{

	public function getReferenceList()
	{
		$modules = \App\ModuleHierarchy::getModulesByLevel(1);
		if (!empty($modules)) {
			return array_keys($modules);
		}
		return [];
	}
}
