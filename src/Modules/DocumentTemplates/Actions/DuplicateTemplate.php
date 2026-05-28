<?php

namespace App\Modules\DocumentTemplates\Actions;

/**
 * Duplicate PDF template.
 *
 * @package FreeCRM
 * @license FreeCRM Public License 1.1
 */
class DuplicateTemplate extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\DocumentTemplates\Models\Module::checkRequestPermission($request, 'CreateView');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('id');
		$source = \App\Modules\DocumentTemplates\Models\Record::getInstanceById(
			$recordId,
			'DocumentTemplates'
		);
		if (!$source) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_NOT_FOUND', 'Vtiger'));
		}

		$moduleName = $source->get('module_name') ?: 'Vtiger';
		$new = \App\Modules\DocumentTemplates\Models\Record::getCleanInstance($moduleName);

		foreach (\App\Modules\DocumentTemplates\Models\Module::$allFields as $field) {
			if ($field === 'watermark_image') {
				$new->set($field, '');
				continue;
			}
			$val = $source->get($field);
			if ($field === 'conditions' && is_array($val)) {
				$val = json_encode($val);
			}
			$new->set($field, $val === null ? '' : $val);
		}

		$primary = (string) $source->get('primary_name');
		$suffix = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TEMPLATE_COPY_SUFFIX', 'Settings:Template');
		$new->set('primary_name', $primary . ' (' . $suffix . ')');
		$new->set('default', 0);

		\App\Modules\DocumentTemplates\Models\Record::saveFullImport($new);

		$sourceWatermark = (string) $source->get('watermark_image');
		if ($sourceWatermark !== '' && is_file($sourceWatermark)) {
			$targetDir = \App\Modules\DocumentTemplates\Models\Module::$uploadPath;
			if (!is_dir($targetDir)) {
				@mkdir($targetDir, 0775, true);
			}
			$imageExt = pathinfo($sourceWatermark, PATHINFO_EXTENSION);
			if ($imageExt === '') {
				$imageExt = 'png';
			}
			$newFilePath = $targetDir . $new->getId() . '.' . $imageExt;
			if (@copy($sourceWatermark, $newFilePath)) {
				$new->set('watermark_image', $newFilePath);
				$new->save();
			}
		}

		header('Location: index.php?module=DocumentTemplates&view=Edit&record=' . (int) $new->getId() . '&mode=Step1');
		exit;
	}
}
