<?php

namespace App\Modules\OSSMail\Views;

/**
 * Mail cction bar class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class MailActionBar extends \Vtiger_Index_View
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$uid = $request->get('uid');
		$folder = $request->get('folder');
		$rcId = $request->get('rcId');
		$params = null; // fixme - non existent

		$account = \App\Modules\OSSMail\Models\Record::getAccountByHash($rcId);
		if (!$account) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
		$rcId = $account['user_id'];
		$mailViewModel = \App\Modules\OSSMailView\Models\Record::getCleanInstance('OSSMailView');
		$record = $mailViewModel->checkMailExist($uid, $folder, $rcId);
		if (!$record && !empty($account['actions'])) {
			$mailModel = \App\Modules\Vtiger\Models\Record::getCleanInstance('OSSMail');
			$mbox = $mailModel->imapConnect($account['username'], $account['password'], $account['mail_host'], $folder);
			$return = \App\Modules\OSSMailScanner\Models\Record::executeActions($account, $mailModel->getMail($mbox, $uid), $folder, $params);
			if (isset($return['CreatedEmail'])) {
				$record = $return['CreatedEmail'];
			}
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $record);
		if ($record) {
			$relatedRecords = $mailViewModel->getRelatedRecords($record);
			$viewer->assign('RELATED_RECORDS', $relatedRecords);
		}
		\App\ModuleHierarchy::getModulesByLevel();
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('URL', \App\AppConfig::main('site_URL'));
		$viewer->view('MailActionBar.tpl', $moduleName);
	}
}
