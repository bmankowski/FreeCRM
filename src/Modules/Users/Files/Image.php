<?php

/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

namespace App\Modules\Users\Files;

/**
 * Serves user avatar images via file.php (storage is not under public/).
 */
class Image
{
	public function getCheckPermission(\App\Http\Vtiger_Request $request): bool
	{
		return true;
	}

	public function get(\App\Http\Vtiger_Request $request): void
	{
		$record = $request->get('record');
		if (empty($record)) {
			throw new \App\Exceptions\NoPermitted('Not Acceptable', 406);
		}
		/** @var \App\Modules\Users\Models\Record $recordModel */
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $request->getModule());
		$relativePath = $recordModel->getImageRelativePath();
		if ($relativePath === null || $relativePath === '') {
			$this->sendDefaultIcon();
			return;
		}
		$path = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
		if (!is_readable($path)) {
			$this->sendDefaultIcon();
			return;
		}
		$file = \App\Fields\File::loadFromPath($path);
		header('Content-Type: ' . $file->getMimeType());
		header('Content-Transfer-Encoding: binary');
		readfile($path);
	}

	public function postCheckPermission(\App\Http\Vtiger_Request $request): bool
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
		$currentUserModel = \App\User\CurrentUser::get();
		$allowed = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Save', $record);
		if ($allowed) {
			if (!$currentUserModel->isAdminUser()) {
				if (empty($record)) {
					$allowed = false;
				} elseif ($currentUserModel->get('id') !== $recordModel->getId()) {
					$allowed = false;
				}
			}
		}
		if (!$allowed) {
			throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function post(\App\Http\Vtiger_Request $request): void
	{
	}

	private function sendDefaultIcon(): void
	{
		$relative = \App\Runtime\Vtiger_Theme::getThemeImageWebUrl('DefaultUserIcon.png');
		if ($relative === false) {
			throw new \App\Exceptions\NoPermitted('Not Found', 404);
		}
		$path = \App\Core\Loader::resolveNameToPath('~' . $relative);
		if (!is_readable($path)) {
			throw new \App\Exceptions\NoPermitted('Not Found', 404);
		}
		$file = \App\Fields\File::loadFromPath($path);
		header('Content-Type: ' . $file->getMimeType());
		header('Content-Transfer-Encoding: binary');
		readfile($path);
	}
}
