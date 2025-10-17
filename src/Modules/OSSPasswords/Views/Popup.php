<?php

namespace App\Modules\OSSPasswords\Views;

/**
 * Popup View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class Popup extends \Vtiger_Index_View
{
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 * @param Vtiger_Request $request
	 * @param CRM_Viewer $viewer
	 */

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, CRM_Viewer $viewer)
	{
		$moduleName = $this->getModule($request);
		$sourceModule = $request->get('src_module');
		$sourceRecord = $request->get('src_record');

		$showFilter = (in_array($sourceModule, ['HelpDesk', 'Project', 'SSalesProcesses']) && $moduleName == 'OSSPasswords') ? true : false;
		//list of records is narrowed to contacts related to help desks account, only in Help Desk Contacts relation view
		if ($showFilter && isRecordExists($sourceRecord) && strpos($_SERVER['QUERY_STRING'], "module=$moduleName&src_module=$sourceModule") === 0) {
			$filterField = ['HelpDesk' => 'parent_id', 'Project' => 'linktoaccountscontacts', 'OSSPasswords' => 'related_to'];
			$relParentModule = 'Accounts';
			$record = \App\Modules\Vtiger\Models\Record::getInstanceById($sourceRecord, $sourceModule);
			$relId = $record->get($filterField[$sourceModule]);
			if (\vtlib\Functions::getCRMRecordType($relId) === $relParentModule) {
				$request->set('related_parent_module', $relParentModule);
				$request->set('related_parent_id', $relId);
				$viewer->assign('SWITCH', true);
				$viewer->assign('POPUP_SWITCH_ON_TEXT', \App\Runtime\Vtiger_Language_Handler::translate('SINGLE_' . $relParentModule, $relParentModule));
			}
		}
		parent::initializeListViewContents($request, $viewer);
		if (array_key_exists('password', $this->listViewHeaders)) {
			foreach ($this->listViewEntries as $recordId => &$recordInstance) {
				$recordInstance->set('password', str_repeat('*', 10));
			}
			$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
		}
	}
}
