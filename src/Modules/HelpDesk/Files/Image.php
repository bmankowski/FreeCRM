<?php

namespace App\Modules\HelpDesk\Files;

/**
 * Serves HelpDesk Image attachments (uitype 69) from s_yf_record_files.
 */
class Image extends \App\Modules\Base\Files\File
{
	public function getCheckPermission(\App\Http\Vtiger_Request $request): bool
	{
		$recordId = $request->getInteger('record');
		if ($recordId <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (!\App\Security\Privilege::isPermitted('HelpDesk', 'DetailView', $recordId)) {
			throw new \App\Exceptions\NoPermitted('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
		$linked = \App\Models\RecordFile::getByRecord($recordId, \App\Models\RecordFile::ROLE_IMAGE) !== null;
		if (!$linked) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		return true;
	}

	public function get(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->getInteger('record');
		$row = \App\Models\RecordFile::getByRecord($recordId, \App\Models\RecordFile::ROLE_IMAGE);
		if (!$row) {
			throw new \App\Exceptions\NoPermitted('LBL_NOT_ACCESSIBLE', 404);
		}

		$path = \App\Models\RecordFile::resolveAbsolutePath((string) ($row['storage_path'] ?? ''));
		if ($path === false || !is_file($path)) {
			throw new \App\Exceptions\NoPermitted('LBL_NOT_ACCESSIBLE', 404);
		}

		$file = \App\Fields\File::loadFromPath($path);
		header('Content-Type: ' . $file->getMimeType());
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: private, max-age=86400');
		header('Content-Disposition: inline; filename="' . ($row['original_name'] ?? basename($path)) . '"');
		readfile($path);

		return false;
	}
}
