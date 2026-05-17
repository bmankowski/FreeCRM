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
		$name = $this->getName();
		if ($name === 'conditions') {
			return new \App\Modules\DocumentTemplates\UiTypes\Conditions($this);
		}
		if ($name === 'template_members') {
			return new \App\Modules\DocumentTemplates\UiTypes\TemplateMembers($this);
		}
		if ($name === 'watermark_image') {
			return new \App\Modules\DocumentTemplates\UiTypes\WatermarkImage($this);
		}
		$params = (string) $this->get('fieldparams');
		if ($params === 'document_template_conditions') {
			return new \App\Modules\DocumentTemplates\UiTypes\Conditions($this);
		}
		if ($params === 'document_template_members') {
			return new \App\Modules\DocumentTemplates\UiTypes\TemplateMembers($this);
		}
		if ($params === 'document_template_watermark') {
			return new \App\Modules\DocumentTemplates\UiTypes\WatermarkImage($this);
		}
		return parent::getUITypeModel();
	}
}
