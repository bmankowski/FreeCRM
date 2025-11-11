<?php

namespace App\Modules\EmailTemplates\Models;

/**
 * EmailTemplates field model class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Field extends \App\Modules\Base\Models\Field
{

	public function isAjaxEditable()
	{
		return false;
	}

	/**
	 * Function to get all the available picklist values for the current field
	 * @return array List of picklist values if the field is of type picklist or multipicklist, null otherwise.
	 */
	public function getModulesListValues()
	{
		$modules = parent::getModulesListValues();
		$modules[\App\Utils\ModuleUtils::getModuleId('Reports')] = ['name' => 'Reports', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Reports', 'Reports')];
		$modules[\App\Utils\ModuleUtils::getModuleId('Users')] = ['name' => 'Users', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Users', 'Users')];
		$modules[\App\Utils\ModuleUtils::getModuleId('Events')] = ['name' => 'Events', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Events', 'Events')];
		$modules[\App\Utils\ModuleUtils::getModuleId('ModComments')] = ['name' => 'ModComments', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('ModComments')];
		return $modules;
	}
}
