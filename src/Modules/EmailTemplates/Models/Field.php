<?php

namespace FreeCRM\Modules\EmailTemplates\Models;

/**
 * EmailTemplates field model class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Field extends \FreeCRM\Modules\Vtiger\Models\Field
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
		$modules[\App\Module::getModuleId('Reports')] = ['name' => 'Reports', 'label' => \LanguageTranslator::translate('Reports', 'Reports')];
		$modules[\App\Module::getModuleId('Users')] = ['name' => 'Users', 'label' => \LanguageTranslator::translate('Users', 'Users')];
		$modules[\App\Module::getModuleId('Events')] = ['name' => 'Events', 'label' => \LanguageTranslator::translate('Events', 'Events')];
		$modules[\App\Module::getModuleId('ModComments')] = ['name' => 'ModComments', 'label' => \LanguageTranslator::translate('ModComments')];
		return $modules;
	}
}
