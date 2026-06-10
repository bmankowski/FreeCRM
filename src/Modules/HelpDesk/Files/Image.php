<?php

namespace App\Modules\HelpDesk\Files;

/**
 * Serves HelpDesk Image attachments (uitype 69) from vtiger_attachments.
 */
class Image extends \App\Modules\Base\Files\File
{
	public function getCheckPermission(\App\Http\Vtiger_Request $request): bool
	{
		$recordId = $request->getInteger('record');
		$attachmentId = $request->getInteger('attachment');
		if ($recordId <= 0 || $attachmentId <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (!\App\Security\Privilege::isPermitted('HelpDesk', 'DetailView', $recordId)) {
			throw new \App\Exceptions\NoPermitted('ERR_NO_PERMISSIONS_FOR_THE_RECORD', 406);
		}
		$linked = (new \App\Db\Query())
			->from('vtiger_seattachmentsrel')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = vtiger_seattachmentsrel.attachmentsid')
			->where([
				'vtiger_seattachmentsrel.crmid' => $recordId,
				'vtiger_seattachmentsrel.attachmentsid' => $attachmentId,
				'vtiger_crmentity.setype' => 'HelpDesk Image',
			])
			->exists();
		if (!$linked) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		return true;
	}

	public function get(\App\Http\Vtiger_Request $request)
	{
		$attachmentId = $request->getInteger('attachment');
		$row = (new \App\Db\Query())
			->from('vtiger_attachments')
			->where(['attachmentsid' => $attachmentId])
			->one();
		if (!$row) {
			throw new \App\Exceptions\NoPermitted('LBL_NOT_ACCESSIBLE', 404);
		}

		$path = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $row['path'] . $row['attachmentsid'] . '_' . $row['name'];
		if (!is_file($path)) {
			throw new \App\Exceptions\NoPermitted('LBL_NOT_ACCESSIBLE', 404);
		}

		$file = \App\Fields\File::loadFromPath($path);
		header('Content-Type: ' . $file->getMimeType());
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: private, max-age=86400');
		header('Content-Disposition: inline; filename="' . $row['name'] . '"');
		readfile($path);

		return false;
	}
}
