<?php

namespace App\Modules\Settings\PDF\Actions;



/**
 * Returns special functions for PDF Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Watermark extends \App\Modules\Settings\Base\Actions\Index
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
		$output = \App\Modules\Settings\PDF\Models\Record::deleteWatermark($pdfModel);

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
		$targetDir = \App\Modules\Settings\PDF\Models\Module::$uploadPath;
		$targetFile = $targetDir . $newName;
		$uploadOk = 1;

		$fileInstance = \App\Fields\File::loadFromPath($_FILES['watermark']['tmp_name'][0]);
		if (!$fileInstance->validate('image')) {
			$uploadOk = 0;
		}

		// Check allowed upload file size
		if ($uploadOk && $_FILES['watermark']['size'][0] > vglobal('upload_maxsize')) {
			$uploadOk = 0;
		}
		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk === 1) {
			$db = \App\Db::getInstance('admin');
			$watermarkImage = (new \App\Db\Query())->select('watermark_image')
				->from('a_#__pdf')
				->where(['pdfid' => $templateId])
				->scalar($db);
			if (file_exists($watermarkImage)) {
				unlink($watermarkImage);
			}
			// successful upload
			if ($fileInstance->moveFile($targetFile)) {
				$db->createCommand()
					->update('a_#__pdf', ['watermark_image' => $targetFile], ['pdfid' => $templateId])
					->execute();
			}
		}
	}
}
