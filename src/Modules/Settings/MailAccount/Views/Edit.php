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

namespace App\Modules\Settings\MailAccount\Views;

class Edit extends \App\Modules\Settings\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function getPageTitle(\App\Http\Vtiger_Request $request): string
	{
		return 'LBL_MAIL_ACCOUNTS';
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$recordId = (int) $request->get('record');
		$recordModel = $recordId
			? \App\Modules\Settings\MailAccount\Models\Record::getInstanceById($recordId)
			: \App\Modules\Settings\MailAccount\Models\Record::getCleanInstance();
		if ($recordId && $recordModel === null) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}

		$kind = (string) ($recordModel->get('kind') ?? 'shared');
		if ($recordId && $kind === 'personal') {
			$ownerUserId = (int) $recordModel->get('owner_user_id');
			$mailAccount = \App\Modules\Mail\Models\Account::getPersonalForDisplay($ownerUserId);
			$ownerUser = $ownerUserId > 0
				? \App\Modules\Users\Models\Record::getInstanceById($ownerUserId, 'Users')
				: null;
			$ownerName = $ownerUser ? $ownerUser->getName() : '';
			$ownerMailboxUrl = $ownerUser ? $ownerUser->getPreferenceMailboxViewUrl() : '';
		} else {
			$kind = 'shared';
			$ownerUserId = 0;
			$ownerName = '';
			$ownerMailboxUrl = '';
			$mailAccount = $recordId
				? (\App\Modules\Mail\Models\Account::getById($recordId) ?? $recordModel->getData())
				: $recordModel->getData();
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->assign('GROUP_OPTIONS', $this->getGroupOptions());
		$viewer->assign('MAIL_ACCOUNT', $mailAccount);
		$viewer->assign('ACCOUNT_KIND', $kind);
		$viewer->assign('OWNER_USER_ID', $ownerUserId);
		$viewer->assign('OWNER_USER_NAME', $ownerName);
		$viewer->assign('OWNER_PREFERENCE_MAILBOX_URL', $ownerMailboxUrl);
		$viewer->view('Edit.tpl', $request->getModule(false));
	}

	private function getGroupOptions(): array
	{
		$options = [];
		$dataReader = (new \App\Db\Query())
			->select(['groupid', 'groupname'])
			->from('vtiger_groups')
			->orderBy(['groupname' => SORT_ASC])
			->createCommand()
			->query();
		while ($row = $dataReader->read()) {
			$options[(int) $row['groupid']] = (string) $row['groupname'];
		}
		return $options;
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request): array
	{
		$scripts = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		return array_merge($scripts, $this->checkAndConvertJsScripts([
			'modules.Settings.Vtiger.resources.Edit',
			'modules.Mail.resources.MailboxForm',
			'modules.Mail.resources.ImapFolderPicker',
			"modules.Settings.$moduleName.resources.Edit",
		]));
	}

	public function getJSLanguageStrings(\App\Http\Vtiger_Request $request)
	{
		$strings = parent::getJSLanguageStrings($request);
		$moduleStrings = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile(
			\App\Runtime\Vtiger_Language_Handler::getLanguage(),
			$request->getModule(false)
		);
		if (!empty($moduleStrings['languageStrings'])) {
			$strings += $moduleStrings['languageStrings'];
		}
		$mailStrings = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile(
			\App\Runtime\Vtiger_Language_Handler::getLanguage(),
			'Mail'
		);
		if (!empty($mailStrings['languageStrings'])) {
			$strings += $mailStrings['languageStrings'];
		}
		return $strings;
	}
}
