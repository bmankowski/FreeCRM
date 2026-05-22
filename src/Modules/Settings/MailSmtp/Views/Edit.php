<?php

namespace App\Modules\Settings\MailSmtp\Views;



/**
 * Edit view class for MailSmtp
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

class Edit extends \App\Modules\Settings\Base\Views\Index
{

	/**
	 * Function proccess
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$record = $request->get('record');
		$fromRecord = $request->get('from_record');
		$recordId = '';
		if (!empty($record)) {
			$recordModel = \App\Modules\Settings\MailSmtp\Models\Record::getInstanceById($record);
			$recordId = $record;
		} elseif (!empty($fromRecord)) {
			$sourceModel = \App\Modules\Settings\MailSmtp\Models\Record::getInstanceById($fromRecord);
			if ($sourceModel) {
				$recordModel = \App\Modules\Settings\MailSmtp\Models\Record::getCleanInstance();
				$data = $sourceModel->getData();
				unset($data['id']);
				$data['default'] = 0;
				$copySuffix = ' (' . \App\Runtime\Vtiger_Language_Handler::translate('LBL_MAILSMTP_COPY_SUFFIX', 'Settings:MailSmtp') . ')';
				$data['name'] = $sourceModel->getName() . $copySuffix;
				$recordModel->setData($data);
			} else {
				$recordModel = \App\Modules\Settings\MailSmtp\Models\Record::getCleanInstance();
			}
		} else {
			$recordModel = \App\Modules\Settings\MailSmtp\Models\Record::getCleanInstance();
		}
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->view('Edit.tpl', $moduleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return array - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.Edit",
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
