<?php

namespace App\Modules\DocumentTemplates\Actions;

/**
 * Duplicate PDF template (Settings → Document templates).
 *
 * @package FreeCRM
 * @license FreeCRM Public License 1.1
 */

class DuplicateTemplate extends \App\Modules\Settings\Base\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('id');
		$source = \App\Modules\Base\Models\PDF::getInstanceById($recordId);
		if ($source === false) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_NOT_FOUND', 'Vtiger'));
		}

		$moduleName = $source->get('module_name') ?: 'Vtiger';
		$new = \App\Modules\DocumentTemplates\Models\Record::getCleanInstance($moduleName);

		foreach (\App\Modules\DocumentTemplates\Models\Module::$allFields as $field) {
			if ($field === 'watermark_image') {
				$new->set($field, '');
				continue;
			}
			$val = $source->getRaw($field);
			$new->set($field, $val === null ? '' : $val);
		}

		$primary = (string) $source->getRaw('primary_name');
		$suffix = \App\Runtime\Vtiger_Language_Handler::translate('LBL_TEMPLATE_COPY_SUFFIX', 'Settings:Template');
		$new->set('primary_name', $primary . ' (' . $suffix . ')');
		$new->set('default', 0);

		\App\Modules\DocumentTemplates\Models\Record::save($new, 'import');

		$sourceWatermark = (string) $source->getRaw('watermark_image');
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
				\App\Modules\DocumentTemplates\Models\Record::save($new, 6);
			}
		}

		header('Location: index.php?module=DocumentTemplates&view=Edit&record=' . (int) $new->getId() . '&mode=Step1');
		exit;
	}
}
