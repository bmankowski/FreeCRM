<?php

namespace App\Modules\Documents\Actions;

/**
 * Action to mass upload files
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class MassAdd extends \App\Runtime\Vtiger_Action_Controller
{

	/**
	 * Function to check permission
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \Exception\NoPermitted
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if (!\App\Modules\Users\Models\Privileges::isPermitted($request->getModule(), 'CreateView')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
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
				$originalFile = [
					'name' => $file['name'][$i],
					'type' => $file['type'][$i],
					'tmp_name' => $file['tmp_name'][$i],
					'error' => $file['error'][$i],
					'size' => $file['size'][$i],
				];
				$recordeModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
				$recordeModel->set('notes_title', $nameFiles[$i]);
				$recordeModel->set('assigned_user_id', \App\User::getCurrentUserId());
				$recordeModel->file = $originalFile;
				$recordeModel->set('filelocationtype', 'I');
				$recordeModel->set('filestatus', true);
				$recordeModel->save();
				
				// Link the document to parent record if createmode is 'link'
				if ($createMode === 'link' && !empty($returnModule) && !empty($returnId)) {
					$parentModuleModel = \App\Modules\Vtiger\Models\Module::getInstance($returnModule);
					$relatedModule = $recordeModel->getModule();
					$relatedRecordId = $recordeModel->getId();
					
					$relationModel = \App\Modules\Vtiger\Models\Relation::getInstance($parentModuleModel, $relatedModule);
					if ($relationModel) {
						$relationModel->addRelation($returnId, $relatedRecordId);
					}
				}
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
