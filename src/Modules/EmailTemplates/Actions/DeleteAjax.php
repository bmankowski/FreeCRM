<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\EmailTemplates\Actions;

class DeleteAjax extends \App\Modules\Base\Actions\Delete
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($request->get('record'), $request->getModule());
		$response = new \App\Http\Vtiger_Response();

		$sysName = trim((string) $recordModel->get('sys_name'));
		if ($sysName !== ''
			&& \App\Modules\EmailTemplates\Models\RecruitmentTemplate::isShortNameUsedInMatrix($sysName)) {
			$response->setResult([
				'success' => false,
				'message' => \App\Runtime\Vtiger_Language_Handler::translate(
					'LBL_ERR_SYS_NAME_MATRIX_IN_USE',
					$request->getModule()
				),
			]);
			$response->emit();

			return;
		}

		$deleted = (bool) $recordModel->delete();
		if ($deleted) {
			\App\Email\Mail::clearTemplateListCache();
		}
		$response->setResult(['success' => $deleted]);
		$response->emit();
	}
}
