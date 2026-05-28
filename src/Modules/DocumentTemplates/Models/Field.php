<?php

namespace App\Modules\DocumentTemplates\Models;

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
		$modules[\App\Utils\ModuleUtils::getModuleId('ModComments')] = ['name' => 'ModComments', 'label' => \App\Runtime\Vtiger_Language_Handler::translate('ModComments')];
		return $modules;
	}

	public function getUITypeModel()
	{
		if ($this->get('uitypeModel')) {
			return $this->get('uitypeModel');
		}
		$name = $this->getName();
		$params = (string) $this->get('fieldparams');
		$class = null;
		if ($name === 'conditions' || $params === 'document_template_conditions') {
			$class = \App\Modules\DocumentTemplates\UiTypes\Conditions::class;
		}
		if ($class !== null) {
			$this->set('uitypeModel', (new $class())->set('field', $this));
			return $this->get('uitypeModel');
		}
		return parent::getUITypeModel();
	}
}
