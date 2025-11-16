<?php
/**
 * Basic class to handle files
 * @package YetiForce.Files
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

/**
 * Basic class to handle files
 */

namespace App\Modules\Base\Files;

abstract class File  
{

	/**
	 * Storage name
	 * @var string 
	 */
	public $storageName = '';

	/**
	 * Checking permission in get method
	 * @param \App\Http\Vtiger_Request $request
	 * @return boolean
	 */
	public function getCheckPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$field = $request->get('field');
		if ($record) {
			if (!\App\Security\Privilege::isPermitted($moduleName, 'DetailView', $record) || !\App\Fields\Field::getFieldPermission($moduleName, $field)) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
		} else {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	/**
	 * Checking permission in post method
	 * @param \App\Http\Vtiger_Request $request
	 * @return boolean
	 */
	public function postCheckPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$field = $request->get('field');
		if (!empty($record)) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
			if (!$recordModel->isEditable() || !\App\Fields\Field::getFieldPermission($moduleName, $field, false)) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
		} else {
			if (!\App\Fields\Field::getFieldPermission($moduleName, $field, false) || !\App\Security\Privilege::isPermitted($moduleName, 'CreateView')) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
		}
		return true;
	}

	/**
	 * Get and save files
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function post(\App\Http\Vtiger_Request $request)
	{
		$attachIds = [];
		$files = \App\Modules\Base\Helpers\Util::transformUploadedFiles($_FILES, true);
		foreach ($files as $key => $file) {
			foreach ($file as $key => $fileData) {
				$result = \App\Modules\Base\Models\Files::uploadAndSave($fileData, $this->getFileType(), $this->getStorageName());
				if ($result) {
					$attach[] = ['id' => $result, 'name' => $fileData['name']];
				}
			}
		}
		if ($request->isAjax()) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult([
				'field' => $request->get('field'),
				'module' => $request->getModule(),
				'attach' => $attach
			]);
			$response->emit();
		}
	}

	/**
	 * Get storage name
	 * @return string
	 */
	public function getStorageName()
	{
		return $this->storageName;
	}

	/**
	 * Get file type
	 * @return string
	 */
	public function getFileType()
	{
		return $this->fileType;
	}
}
