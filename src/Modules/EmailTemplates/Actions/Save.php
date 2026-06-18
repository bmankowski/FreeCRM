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

class Save extends \App\Modules\Base\Actions\Save
{
	private const RECRUITMENT_MODULE = 'ProjektyRekrutacyjne';

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->validateRecruitmentTemplate($request);

		return parent::process($request);
	}

	public function saveRecord(\App\Http\Vtiger_Request $request)
	{
		$recordModel = parent::saveRecord($request);
		\App\Email\Mail::clearTemplateListCache();
		if (\App\Cache\Cache::has('MailTempleteDetail', $recordModel->getId())) {
			\App\Cache\Cache::delete('MailTempleteDetail', $recordModel->getId());
		}

		return $recordModel;
	}

	private function validateRecruitmentTemplate(\App\Http\Vtiger_Request $request): void
	{
		$moduleName = trim((string) $request->get('module_name'));
		if ($moduleName !== self::RECRUITMENT_MODULE) {
			return;
		}

		$accountIdsValue = $request->get('account_id');
		$sysName = trim((string) $request->get('sys_name'));
		if ($sysName === '') {
			throw new \App\Exceptions\AppException('LBL_ERR_SYS_NAME_REQUIRED');
		}
		if (strlen($sysName) > 50) {
			throw new \App\Exceptions\AppException('LBL_ERR_SYS_NAME_TOO_LONG');
		}

		$recordId = $request->getInteger('record');
		if ($recordId > 0) {
			$existing = \App\Modules\Base\Models\Record::getInstanceById($recordId, 'EmailTemplates');
			$oldSysName = trim((string) $existing->get('sys_name'));
			if ($oldSysName !== '' && $oldSysName !== $sysName
				&& \App\Modules\EmailTemplates\Models\RecruitmentTemplate::isShortNameUsedInMatrix($oldSysName)) {
				throw new \App\Exceptions\AppException('LBL_ERR_SYS_NAME_MATRIX_IN_USE');
			}
		}

		\App\Modules\EmailTemplates\Models\TemplateAccount::assertNoSysNameOverlap(
			$recordId,
			$sysName,
			$accountIdsValue
		);
	}
}
