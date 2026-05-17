<?php

namespace App\Modules\TemplateElements\Models;

class Field extends \App\Modules\Base\Models\Field
{
	public function isAjaxEditable()
	{
		return false;
	}

	public function getModulesListValues()
	{
		$modules = parent::getModulesListValues();
		$modules[\App\Utils\ModuleUtils::getModuleId('Reports')] = ['name' => 'Reports', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Reports', 'Reports')];
		$modules[\App\Utils\ModuleUtils::getModuleId('Users')] = ['name' => 'Users', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Users', 'Users')];
		$modules[\App\Utils\ModuleUtils::getModuleId('Events')] = ['name' => 'Events', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Events', 'Events')];
		return $modules;
	}
}
