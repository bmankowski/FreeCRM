<?php

namespace App\Modules\Vtiger\UiTypes;

/**
 * UIType ReferenceLink Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class ReferenceLink extends Base
{

	public function isAjaxEditable()
	{
		return false;
	}

	public function getReferenceList()
	{
		$modules = \App\ModuleHierarchy::getModulesByLevel();
		return array_keys($modules);
	}

	public function getListSearchTemplateName()
	{
		if (\App\AppConfig::performance('SEARCH_REFERENCE_BY_AJAX')) {
			return 'uitypes/ReferenceSearchView.tpl';
		}
		return \App\Modules\Vtiger\UiTypes\Base::getListSearchTemplateName();
	}
}
