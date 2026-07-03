<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Mail\Actions;

class DownloadAttachment extends Base
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		$id = (int) $request->get('id');
		$row = (new \App\Db\Query())->from('u_yf_mail_attachments')->where(['id' => $id])->one();
		if (!$row) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		$path = ROOT_DIRECTORY . $row['storage_path'];
		if (!is_file($path)) {
			throw new \App\Exceptions\AppException('File not found');
		}
		header('Content-Type: ' . $row['mime_type']);
		header('Content-Disposition: attachment; filename="' . basename($row['original_name']) . '"');
		readfile($path);
		exit;
	}
}
