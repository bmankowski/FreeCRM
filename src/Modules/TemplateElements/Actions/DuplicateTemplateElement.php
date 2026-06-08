<?php

namespace App\Modules\TemplateElements\Actions;

/**
 * Duplicate template element record.
 *
 * @package FreeCRM
 * @license FreeCRM Public License 1.1
 */
class DuplicateTemplateElement extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if (!\App\Modules\Users\Models\Privileges::isPermitted('TemplateElements', 'EditView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = (int) $request->get('id');
		$source = \App\Modules\TemplateElements\Models\Record::getInstanceById(
			$recordId,
			'TemplateElements'
		);
		if (!$source || !$source->getId()) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_NOT_FOUND', 'Vtiger'));
		}

		$new = \App\Modules\TemplateElements\Models\Record::getCleanInstance('TemplateElements');
		foreach (\App\Modules\TemplateElements\Models\Module::$copyFields as $field) {
			$val = $source->get($field);
			$new->set($field, $val === null ? '' : $val);
		}

		$label = (string) $source->get('label');
		$suffix = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TEMPLATE_ELEMENT_COPY_SUFFIX', 'TemplateElements');
		$new->set('label', $label . ' (' . $suffix . ')');
		$new->set('code', \App\Modules\TemplateElements\Models\Record::buildDuplicateCode(
			(string) $source->get('code'),
			(string) $source->get('module_name'),
			(string) $source->get('language')
		));

		$new->save();

		header('Location: index.php?module=TemplateElements&view=Edit&record=' . (int) $new->getId());
		exit;
	}
}
