<?php

namespace App\Modules\DocumentTemplates\Actions;



/**
 * Returns special functions for PDF Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Watermark extends \App\Modules\Base\Actions\Config
{

	public function __construct()
	{
		$this->exposeMethod('Delete');
		$this->exposeMethod('Upload');
	}

	public function Delete(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('id');
		$pdfModel = \App\Modules\Base\Models\PDF::getInstanceById($recordId);
		$output = \App\Modules\DocumentTemplates\Models\Record::deleteWatermark((int) $recordId);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}

	public function Upload(\App\Http\Vtiger_Request $request)
	{
		$templateId = $request->get('template_id');
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

		// Check allowed upload file size
		if ($uploadOk && $_FILES['watermark']['size'][0] > \App\Core\AppConfig::main('upload_maxsize')) {
			$uploadOk = 0;
		}
		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk === 1) {
			$db = \App\Db\Db::getInstance('admin');
			$watermarkImage = (new \App\Db\Query())->select('watermark_image')
				->from('u_yf_documenttemplates')
				->where(['documenttemplatesid' => $templateId])
				->scalar($db);
			if (file_exists($watermarkImage)) {
				unlink($watermarkImage);
			}
			// successful upload
			if ($fileInstance->moveFile($targetFile)) {
				$db->createCommand()
					->update('u_yf_documenttemplates', ['watermark_image' => $targetFile], ['documenttemplatesid' => $templateId])
					->execute();
			}
		}
	}
}
