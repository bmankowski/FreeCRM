<?php

namespace App\Modules\DocumentTemplates\Actions;

/**
 * Watermark upload/delete for document templates.
 */
class Watermark extends \App\Modules\Base\Actions\Config
{
	public function __construct()
	{
		$this->exposeMethod('Delete');
		$this->exposeMethod('Upload');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\DocumentTemplates\Models\Module::checkRequestPermission($request, 'EditView');
	}

	public function Delete(\App\Http\Vtiger_Request $request)
	{
		$recordId = (int) $request->get('id');
		$output = \App\Modules\DocumentTemplates\Models\Record::deleteWatermark($recordId);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}

	public function Upload(\App\Http\Vtiger_Request $request)
	{
		$templateId = (int) $request->get('template_id');
		$newName = basename($_FILES['watermark']['name'][0]);
		$newName = explode('.', $newName);
		$newName = $templateId . '.' . end($newName);
		$targetDir = \App\Modules\DocumentTemplates\Models\Module::$uploadPath;
		$targetFile = $targetDir . $newName;
		$uploadOk = 1;

		$fileInstance = \App\Fields\File::loadFromPath($_FILES['watermark']['tmp_name'][0]);
		if (!$fileInstance->validate('image')) {
			$uploadOk = 0;
		}

		if ($uploadOk && $_FILES['watermark']['size'][0] > \App\Core\AppConfig::main('upload_maxsize')) {
			$uploadOk = 0;
		}
		if ($uploadOk === 1) {
			$recordModel = \App\Modules\DocumentTemplates\Models\Record::getInstanceById(
				$templateId,
				'DocumentTemplates'
			);
			if (!$recordModel) {
				return;
			}
			$watermarkImage = (string) $recordModel->get('watermark_image');
			if ($watermarkImage !== '' && file_exists($watermarkImage)) {
				unlink($watermarkImage);
			}
			if ($fileInstance->moveFile($targetFile)) {
				$recordModel->set('watermark_image', $targetFile);
				$recordModel->save();
			}
		}
	}
}
