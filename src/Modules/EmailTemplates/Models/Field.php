<?php

namespace App\Modules\EmailTemplates\Models;

/**
 * EmailTemplates field model class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Field extends \App\Modules\Vtiger\Models\Field
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
		$modules[\App\Module::getModuleId('Reports')] = ['name' => 'Reports', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Reports', 'Reports')];
		$modules[\App\Module::getModuleId('Users')] = ['name' => 'Users', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Users', 'Users')];
		$modules[\App\Module::getModuleId('Events')] = ['name' => 'Events', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('Events', 'Events')];
		$modules[\App\Module::getModuleId('ModComments')] = ['name' => 'ModComments', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('ModComments')];
		return $modules;
	}
}
