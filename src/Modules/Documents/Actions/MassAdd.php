<?php

namespace App\Modules\Documents\Actions;

/**
 * Action to mass upload files
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class MassAdd extends \App\Base\Controllers\BaseActionController
{

	/**
	 * Function to check permission
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermitted
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if (!\App\Modules\Users\Models\Privileges::isPermitted($request->getModule(), 'CreateView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$nameFiles = $request->get('nameFile');
		$createMode = $request->get('createmode');
		$returnModule = $request->get('return_module');
		$returnId = $request->get('return_id');
		
		foreach ($_FILES as $file) {
			$countFiles = count($file['name']);
			for ($i = 0; $i < $countFiles; $i++) {
				if (($file['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
					continue;
				}
				if (($file['size'][$i] ?? 0) <= 0 || ($file['name'][$i] ?? '') === '') {
					continue;
				}
				$originalFile = [
					'name' => $file['name'][$i],
					'type' => $file['type'][$i],
					'tmp_name' => $file['tmp_name'][$i],
					'error' => $file['error'][$i],
					'size' => $file['size'][$i],
				];
				$recordeModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
				$recordeModel->set('notes_title', $nameFiles[$i] ?? $originalFile['name']);
				$recordeModel->set('assigned_user_id', $request->getUserId());
				$recordeModel->setPendingUploadFile($originalFile);
				$recordeModel->set('location_type', 'internal');
				$recordeModel->set('active', true);
				$recordeModel->save($request);
				
				// Link the document to parent record if createmode is 'link'
				if ($createMode === 'link' && !empty($returnModule) && !empty($returnId)) {
					$relatedRecordId = (int) $recordeModel->getId();
					if ($returnModule === 'EmailTemplates') {
						\App\Modules\EmailTemplates\Models\TemplateAttachment::link((int) $returnId, [$relatedRecordId]);
					} else {
						$parentModuleModel = \App\Modules\Base\Models\Module::getInstance($returnModule);
						$relatedModule = $recordeModel->getModule();
						$relationModel = \App\Modules\Base\Models\Relation::getInstance($parentModuleModel, $relatedModule);
						if ($relationModel) {
							$relationModel->addRelation($returnId, $relatedRecordId);
						}
					}
				}
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
