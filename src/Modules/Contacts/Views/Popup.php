<?php

namespace App\Modules\Contacts\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class Popup  extends \App\Modules\Base\Views\Index
{
	/*
	 * Function to initialize the required data in smarty to display the List View Contents
	 * @param \App\Http\Vtiger_Request $request
	 * @param CRM_Viewer $viewer
	 */

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, CRM_Viewer $viewer)
	{
		$moduleName = $this->getModule($request);
		$sourceModule = $request->get('src_module');
		$sourceRecord = $request->get('src_record');
		$relParentModule = 'Accounts';
		//list of records is narrowed to contacts related to help desks account, only in Help Desk Contacts relation view
		if ($moduleName == 'Contacts' && $sourceModule == 'HelpDesk' && \App\Utils\Utils::isRecordExists($sourceRecord) && strpos($_SERVER['QUERY_STRING'], 'module=Contacts&src_module=HelpDesk') === 0) {
			$helpDeskRecord = \App\Modules\Base\Models\Record::getInstanceById($sourceRecord, 'HelpDesk');
			$relId = $helpDeskRecord->get('parent_id');
			if (\App\Records\Record::getType($relId) === $relParentModule) {
				$request->set('related_parent_module', $relParentModule);
				$request->set('related_parent_id', $relId);
				$viewer->assign('SWITCH', true);
				$viewer->assign('POPUP_SWITCH_ON_TEXT', \App\Runtime\Vtiger_Language_Handler::translate('SINGLE_' . $relParentModule, $relParentModule));
			}
		}
		if ($moduleName == 'Contacts' && $sourceModule == 'SSalesProcesses' && \App\Utils\Utils::isRecordExists($sourceRecord) && strpos($_SERVER['QUERY_STRING'], 'module=Contacts&src_module=SSalesProcesses') === 0) {
			$moduleRecord = \App\Modules\Base\Models\Record::getInstanceById($sourceRecord, 'SSalesProcesses');
			$relId = $moduleRecord->get('related_to');
			if (\App\Records\Record::getType($relId) === $relParentModule) {
				$request->set('related_parent_module', $relParentModule);
				$request->set('related_parent_id', $relId);
				$viewer->assign('SWITCH', true);
				$viewer->assign('POPUP_SWITCH_ON_TEXT', \App\Runtime\Vtiger_Language_Handler::translate('SINGLE_' . $relParentModule, $relParentModule));
			}
		}
		if ($moduleName == 'Contacts' && $sourceModule == 'Project' && \App\Utils\Utils::isRecordExists($sourceRecord) && strpos($_SERVER['QUERY_STRING'], 'module=Contacts&src_module=Project') === 0) {
			$moduleRecord = \App\Modules\Base\Models\Record::getInstanceById($sourceRecord, 'Project');
			$relId = $moduleRecord->get('linktoaccountscontacts');
			if (\App\Records\Record::getType($relId) === $relParentModule) {
				$request->set('related_parent_module', $relParentModule);
				$request->set('related_parent_id', $relId);
				$viewer->assign('SWITCH', true);
				$viewer->assign('POPUP_SWITCH_ON_TEXT', \App\Runtime\Vtiger_Language_Handler::translate('SINGLE_' . $relParentModule, $relParentModule));
			}
		}

		parent::initializeListViewContents($request, $viewer);
	}
}
