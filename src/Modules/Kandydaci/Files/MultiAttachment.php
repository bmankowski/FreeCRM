<?php

namespace App\Modules\Kandydaci\Files;

/**
 * File handler for serving CV image stored in `cv_img_file`.
 *
 * Expected URL:
 * file.php?module=Kandydaci&action=MultiAttachment&field=cv_img_file&record=<id>&key=<hash>
 */
class MultiAttachment
{
	public function getCheckPermission(\App\Http\Vtiger_Request $request): bool
	{
		$moduleName = $request->getModule();
		$recordId = $request->getInteger('record');
		$field = (string) $request->get('field');
		if (empty($recordId) || empty($field)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (!\App\Security\Privilege::isPermitted($moduleName, 'DetailView', $recordId) || !\App\Fields\Field::getFieldPermission($moduleName, $field)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function get(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->getInteger('record');
		$field = (string) $request->get('field');
		$key = (string) $request->get('key');
		if (empty($recordId) || empty($field) || empty($key)) {
			throw new \App\Exceptions\NoPermitted('Not Acceptable', 406);
		}
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $request->getModule());
		$value = (string) $recordModel->get($field);
		if (empty($value)) {
			throw new \App\Exceptions\NoPermitted('Not Found', 404);
		}
		$decoded = \App\Utils\Json::decode($value);
		if (!is_array($decoded)) {
			throw new \App\Exceptions\NoPermitted('Not Found', 404);
		}
		$item = null;
		foreach ($decoded as $row) {
			if (is_array($row) && ($row['key'] ?? null) === $key) {
				$item = $row;
				break;
			}
		}
		if (!$item || empty($item['path'])) {
			throw new \App\Exceptions\NoPermitted('Not Found', 404);
		}

		$relativeDir = (string) $item['path'];
		// Normalize: ensure trailing slash.
		if ($relativeDir !== '' && substr($relativeDir, -1) !== '/' && substr($relativeDir, -1) !== DIRECTORY_SEPARATOR) {
			$relativeDir .= DIRECTORY_SEPARATOR;
		}
		$absolutePath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $relativeDir . $key;
		if (!file_exists($absolutePath)) {
			\App\Log\Log::warning('[MultiAttachment] File not found: ' . $absolutePath);
			throw new \App\Exceptions\NoPermitted('Not Found', 404);
		}

		$mime = $item['type'] ?? \App\Fields\File::getMimeContentType($absolutePath);
		$name = $item['name'] ?? 'file';
		header('Pragma: cache');
		header('Cache-control: max-age=86400, public');
		header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
		header('Content-Type: ' . $mime);
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: inline; filename="' . addslashes($name) . '"');
		header('Content-Length: ' . filesize($absolutePath));
		readfile($absolutePath);
		return false;
	}
}

