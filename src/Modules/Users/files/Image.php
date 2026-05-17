<?php

namespace App\Modules\Users\files;

/*
 * Basic class to handle files
 * @package YetiForce.Files
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

/**
 * Basic class to handle files
 */
class Image {

	public function getCheckPermission(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function get(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		if (empty($record)) {
			throw new \App\Exceptions\NoPermitted('Not Acceptable', 406);
		}
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $request->getModule());
		$path = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $recordModel->getImagePath();
		$file = \App\Fields\File::loadFromPath($path);
		header('Content-Type: ' . $file->getMimeType());
		header("Content-Transfer-Encoding: binary");
		//header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		readfile($path);
	}

	public function postCheckPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
		$currentUserModel = \App\User\CurrentUser::get();
		// Check for operation access.
		$allowed = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Save', $record);
		if ($allowed) {
			// Deny access if not administrator or account-owner or self
			if (!$currentUserModel->isAdminUser()) {
				if (empty($record)) {
					$allowed = false;
				} else if ($currentUserModel->get('id') !== $recordModel->getId()) {
					$allowed = false;
				}
			}
		}
		if (!$allowed) {
			throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function post(\App\Http\Vtiger_Request $request)
	{

	}
}
