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

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$recordId = (int) $request->get('record');
		$recordModel = $recordId
			? \App\Modules\Settings\MailAccount\Models\Record::getInstanceById($recordId)
			: \App\Modules\Settings\MailAccount\Models\Record::getCleanInstance();
		if ($recordId && $recordModel === null) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->assign('USER_OPTIONS', $this->getUserOptions());
		$viewer->view('Edit.tpl', $request->getModule(false));
	}

	private function getUserOptions(): array
	{
		$options = [];
		$dataReader = (new \App\Db\Query())
			->select(['id', 'user_name', 'first_name', 'last_name'])
			->from('vtiger_users')
			->where(['status' => 'Active'])
			->createCommand()
			->query();
		while ($row = $dataReader->read()) {
			$label = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
			if ($label === '') {
				$label = $row['user_name'];
			}
			$options[(int) $row['id']] = $label . ' (' . $row['user_name'] . ')';
		}
		return $options;
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request): array
	{
		$scripts = parent::getFooterScripts($request);
		$module = $request->getModule(false);
		return array_merge($scripts, $this->checkAndConvertJsScripts([
			'modules.Mail.resources.ImapFolderPicker',
			"modules.Settings.$module.resources.Edit",
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
