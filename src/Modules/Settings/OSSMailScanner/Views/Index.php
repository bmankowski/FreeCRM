<?php

namespace App\Modules\Settings\OSSMailScanner\Views;
use App\Modules\Settings\Vtiger\Models\CustomRecordNumberingModule;

use App\Http\Vtiger_Request;
use App\Modules\Vtiger\Models\Record;

/**
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Index extends \App\Modules\Settings\Vtiger\Views\Index
{

	private $prefixesForModules = ['Project', 'HelpDesk', 'SSalesProcesses', 'Campaigns'];

	public function process(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$mailModuleActive = \vtlib\Functions::getModuleId('OSSMail');
		/** @var OSSMail_Record_Model $mailScannerRecordModel */
		$mailScannerRecordModel = Record::getCleanInstance('OSSMailScanner');
		$identityList = [];
		if ($mailModuleActive) {
			$accountsList = OSSMail_Record_Model::getAccountsList();
			foreach ($accountsList as $key => $account) {
				$identityList[$account['user_id']] = OSSMailScanner_Record_Model::getIdentities($account['user_id']);
			}
		}

		$actionsList = $mailScannerRecordModel->getActionsList();
		$ConfigFolderList = $mailScannerRecordModel->getConfigFolderList();
		$emailSearch = $mailScannerRecordModel->getEmailSearch();
		$emailSearchList = $mailScannerRecordModel->getEmailSearchList();
		$widgetCfg = $mailScannerRecordModel->getConfig(false);
		$supportedModules = \App\Modules\Settings\Vtiger\Models\CustomRecordNumberingModule::getSupportedModules();
		foreach ($supportedModules as $supportedModule) {
			if (in_array($supportedModule->name, $this->prefixesForModules)) {
				$numbering[$supportedModule->name] = \App\Fields\RecordNumber::getNumber($supportedModule->name);
			}
		}

	$checkCron = $mailScannerRecordModel->get_cron();
	$usersEntityInfo = \App\Module::getEntityInfo('Users');
	$viewer = $this->getViewer($request);
	$viewer->assign('RECORD_MODEL', $mailScannerRecordModel);
	$viewer->assign('ACCOUNTS_LIST', $accountsList);
	$viewer->assign('ACTIONS_LIST', $actionsList);
	$viewer->assign('CONFIGFOLDERLIST', $ConfigFolderList);
	$viewer->assign('WIDGET_CFG', $widgetCfg);
	$viewer->assign('EMAILSEARCH', $emailSearch);
	$viewer->assign('EMAILSEARCHLIST', $emailSearchList);
	$viewer->assign('RECORDNUMBERING', $numbering);
	$viewer->assign('ERRORNOMODULE', !$mailModuleActive);
	$viewer->assign('MODULENAME', $moduleName);
	$viewer->assign('IDENTITYLIST', $identityList);
	$viewer->assign('CHECKCRON', $checkCron);
	$viewer->assign('USERS_ENTITY_INFO', $usersEntityInfo);
		echo $viewer->view('Index.tpl', $request->getModule(false), true);
	}
}
