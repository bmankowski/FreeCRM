<?php

namespace App\Modules\DocumentTemplates\Views;



/**
 * List View Class for PDF Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Import extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		
		\App\Log\Log::trace('Start ' . __METHOD__);
		$qualifiedModule = $request->getModule(false);
		$viewer = $this->getViewer($request);

		if ($request->has('upload') && $request->get('upload') == 'true') {
			$xmlName = $_FILES['imported_xml']['name'];
			$uploadedXml = $_FILES['imported_xml']['tmp_name'];
			$xmlError = $_FILES['imported_xml']['error'];
			$extension = end(explode('.', $xmlName));
			$imagePath = '';
			$base64Image = false;

			$recordModel = \App\Modules\DocumentTemplates\Models\Record::getCleanInstance();
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
									$recordModel->set($columnKey, '');
									break;

								default:
									$value = (string) $columnValue;
									$recordModel->set($columnKey, $value);
							}
						}
					}
				}
				\App\Modules\DocumentTemplates\Models\Record::saveFullImport($recordModel);

				if ($recordModel->getId() && $imagePath != '' && $base64Image) {
					$targetDir = \App\Modules\DocumentTemplates\Models\Module::$uploadPath;
					$imageExt = end(explode('.', basename($imagePath)));
					$imageData = base64_decode($base64Image);
					$newFilePath = $targetDir . $recordModel->getId() . '.' . $imageExt;

					$recordModel->set('watermark_image', $newFilePath);
					$recordModel->save();
					file_put_contents($newFilePath, $imageData);
				}
				$viewer->assign('RECORDID', $recordModel->getId());
				$viewer->assign('UPLOAD', true);
			} else {
				$viewer->assign('UPLOAD_ERROR', \App\Runtime\Vtiger_Language_Handler::translate('LBL_UPLOAD_ERROR', $qualifiedModule));
				$viewer->assign('UPLOAD', false);
			}
		}

		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->assign('PDF_DEFAULT_URL', \App\Modules\Base\Models\Module::getInstance('DocumentTemplates')->getDefaultUrl());
		$viewer->view('Import.tpl', $qualifiedModule);
		\App\Log\Log::trace('End ' . __METHOD__);
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();
		$cssFileNames = [
			"modules.$moduleName.Edit",
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}
}
