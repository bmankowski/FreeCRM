<?php

namespace App\Modules\Settings\PDF\Views;



/**
 * List View Class for PDF Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Import extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		
		\App\Log::trace('Start ' . __METHOD__);
		$qualifiedModule = $request->getModule(false);
		$viewer = $this->getViewer($request);

		if ($request->has('upload') && $request->get('upload') == 'true') {
			$xmlName = $_FILES['imported_xml']['name'];
			$uploadedXml = $_FILES['imported_xml']['tmp_name'];
			$xmlError = $_FILES['imported_xml']['error'];
			$extension = end(explode('.', $xmlName));
			$imagePath = '';
			$base64Image = false;

			$pdfModel = \App\Modules\Settings\PDF\Models\Record::getCleanInstance();
			if ($xmlError == UPLOAD_ERR_OK && $extension === 'xml') {
				$xml = simplexml_load_file($uploadedXml);

				foreach ($xml as $fieldsKey => $fieldsValue) {
					foreach ($fieldsValue as $fieldKey => $fieldValue) {
						foreach ($fieldValue as $columnKey => $columnValue) {
							switch ($columnKey) {
								case 'imageblob':
									$base64Image = (string) $columnValue;
									break;

								case 'watermark_image':
									$imagePath = (string) $columnValue;
									$pdfModel->set($columnKey, '');
									break;

								default:
									$value = (string) $columnValue;
									$pdfModel->set($columnKey, $value);
							}
						}
					}
				}
				\App\Modules\Settings\PDF\Models\Record::save($pdfModel, 'import');

				if ($pdfModel->getId() && $imagePath != '' && $base64Image) {
					$targetDir = \App\Modules\Settings\PDF\Models\Module::$uploadPath;
					$imageExt = end(explode('.', basename($imagePath)));
					$imageData = base64_decode($base64Image);
					$newFilePath = $targetDir . $pdfModel->getId() . '.' . $imageExt;

					$pdfModel->set('watermark_image', $newFilePath);
					\App\Modules\Settings\PDF\Models\Record::save($pdfModel, 8);
					file_put_contents($newFilePath, $imageData);
				}
				$viewer->assign('RECORDID', $pdfModel->getId());
				$viewer->assign('UPLOAD', true);
			} else {
				$viewer->assign('UPLOAD_ERROR', \App\Runtime\Vtiger_Language_Handler::translate('LBL_UPLOAD_ERROR', $qualifiedModule));
				$viewer->assign('UPLOAD', false);
			}
		}

		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->view('Import.tpl', $qualifiedModule);
		\App\Log::trace('End ' . __METHOD__);
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();
		$cssFileNames = [
			"modules.Settings.$moduleName.Edit",
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}
}
