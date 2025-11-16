<?php

namespace App\Modules\Base\UiTypes;

/**
 * UIType ReferenceLink Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class ReferenceLink extends BaseUiType
{

	public function isAjaxEditable()
	{
		return false;
	}

	public function getReferenceList()
	{
		$modules = \App\Core\ModuleHierarchy::getModulesByLevel();
		return array_keys($modules);
	}

	public function getListSearchTemplateName()
	{
		if (\App\Core\AppConfig::performance('SEARCH_REFERENCE_BY_AJAX')) {
			return 'uitypes/ReferenceSearchView.tpl';
		}
		return \App\Modules\Base\UiTypes\BaseUiType::getListSearchTemplateName();
	}
}
