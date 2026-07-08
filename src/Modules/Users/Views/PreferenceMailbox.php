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

namespace App\Modules\Users\Views;

class PreferenceMailbox extends \App\Modules\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$moduleName = $request->getModule();
		$currentUserModel = $request->getUser();
		$record = $request->get('record');
		if (!\App\Core\AppConfig::security('SHOW_MY_PREFERENCES')) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
		if (!empty($record) && $currentUserModel->get('id') != $record) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
			if ($recordModel->get('status') != 'Active') {
				throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
		}
		if ($currentUserModel->isAdminUser() === true || $currentUserModel->get('id') == $record) {
			return;
		}
		throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
	}

	public function getJSLanguageStrings(\App\Http\Vtiger_Request $request): array
	{
		$strings = parent::getJSLanguageStrings($request);
		$mailStrings = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile(
			\App\Runtime\Vtiger_Language_Handler::getLanguage(),
			'Mail'
		);
		if (!empty($mailStrings['languageStrings'])) {
			$strings += $mailStrings['languageStrings'];
		}
		return $strings;
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true): void
	{
		parent::preProcess($request, false);

		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');
		$viewer = $this->getViewer($request);
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);

		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->assign('MENUS', \App\Modules\Base\Models\Menu::getAll(true));
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('MAIL_ACCOUNT', \App\Modules\Mail\Models\Account::getPersonalForDisplay($recordId));
		$viewer->assign('PREFERENCE_DETAIL_URL', $recordModel->getPreferenceDetailViewUrl());
		$viewer->assign('MAILBOX_FORM_MODE', 'personal');
		$viewer->assign('USER_EMAIL', \App\Modules\Mail\Models\Account::getUserProfileEmail($recordId));
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$this->getViewer($request)->view('PreferenceMailbox.tpl', $request->getModule());
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request): array
	{
		$scripts = $this->stripCkEditorScripts(parent::getFooterScripts($request));

		return array_merge($scripts, $this->checkAndConvertJsScripts([
			'modules.Mail.resources.MailboxForm',
			'modules.Mail.resources.ImapFolderPicker',
		]));
	}
}
