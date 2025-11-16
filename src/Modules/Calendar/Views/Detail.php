<?php

namespace App\Modules\Calendar\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use App\Http\Vtiger_Request;

class Detail extends \App\Modules\Base\Views\Detail
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Prepare all detail view data
		$this->prepareDetailViewData($request);
	}
	
	protected function prepareDetailViewData(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		if (!empty($recordId)) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId);
			$activityType = $recordModel->getType();
			if ($activityType == 'Events')
				$moduleName = 'Events';
		}
		
		if (!$this->record) {
			$this->record = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();
		$recordStrucure = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_DETAIL);
		
		$detailViewLinkParams = array('MODULE' => $moduleName, 'RECORD' => $recordId);
		$detailViewLinks = $this->record->getDetailViewLinks($detailViewLinkParams);
		$navigationInfo = null; //ListViewSession::getListViewNavigation($recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('NAVIGATION', $navigationInfo);
		$viewer->assign('NO_PAGINATION', true);
		
		//Intially make the prev and next records as null
		$prevRecordId = null;
		$nextRecordId = null;
		$found = false;
		if ($navigationInfo) {
			foreach ($navigationInfo as $page => $pageInfo) {
				foreach ($pageInfo as $index => $record) {
					//If record found then next record in the interation
					//will be next record
					if ($found) {
						$nextRecordId = $record;
						break;
					}
					if ($record == $recordId) {
						$found = true;
					}
					//If record not found then we are assiging previousRecordId
					//assuming next record will get matched
					if (!$found) {
						$prevRecordId = $record;
					}
				}
				//if record is found and next record is not calculated we need to perform iteration
				if ($found && !empty($nextRecordId)) {
					break;
				}
			}
		}

		$moduleModel = $this->record->getModule();
		if (!empty($prevRecordId)) {
			$viewer->assign('PREVIOUS_RECORD_URL', $moduleModel->getDetailViewUrl($prevRecordId));
		}
		if (!empty($nextRecordId)) {
			$viewer->assign('NEXT_RECORD_URL', $moduleModel->getDetailViewUrl($nextRecordId));
		}

		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);

		$viewer->assign('IS_EDITABLE', $this->record->getRecord()->isEditable($moduleName));
		$viewer->assign('IS_DELETABLE', $this->record->getRecord()->isDeletable($moduleName));

		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$linkModels = $this->record->getSideBarLinks($linkParams);

		// Process sidebar links to determine active link
		$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);

		$viewer->assign('QUICK_LINKS', $linkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);
		$viewer->assign('NO_SUMMARY', true);
		
		$structuredValues = $recordStrucure->getStructure();
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStrucure);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));

		if ($moduleName == 'Events') {
			$invitees = $recordModel->getInvities();
			// Enrich invitees with record metadata (replacing vtlib\Functions::getCRMRecordMetadata)
			$inviteeIds = array_filter(array_column($invitees, 'crmid'));
			if (!empty($inviteeIds)) {
				$metadata = (new \App\Db\Query())
					->select(['crmid', 'setype', 'deleted', 'smcreatorid', 'smownerid', 'createdtime', 'private'])
					->from('vtiger_crmentity')
					->where(['in', 'crmid', $inviteeIds])
					->indexBy('crmid')
					->all();
				// Add metadata and labels to each invitee
				foreach ($invitees as &$invitee) {
					if (!empty($invitee['crmid']) && isset($metadata[$invitee['crmid']])) {
						$invitee['metadata'] = $metadata[$invitee['crmid']];
						$invitee['metadata']['label'] = \App\Record::getLabel($invitee['crmid']);
						// Prepare translated module name for template (replaces Vtiger_Language_Handler::getTranslateSingularModuleName)
						if (!empty($invitee['metadata']['setype'])) {
							$invitee['metadata']['module_label'] = \App\Runtime\Vtiger_Language_Handler::getTranslateSingularModuleName($invitee['metadata']['setype']);
							// Also prepare full title for convenience
							$invitee['title'] = $invitee['metadata']['module_label'] . ': ' . $invitee['metadata']['label'] . ' - ' . $invitee['email'];
						}
					}
				}
			}
			$viewer->assign('INVITIES_SELECTED', $invitees);
		}
	}

	/**
	 * Function shows the entire detail for the record
	 * @param \App\Http\Vtiger_Request $request
	 * @return <type>
	 */
	public function showModuleDetailView(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		
		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId);
			$activityType = $recordModel->getType();
			if ($activityType == 'Events')
				$moduleName = 'Events';
		}
		
		// Data already assigned in preProcess, just render
		return $viewer->view('DetailViewFullContents.tpl', $moduleName, true);
	}

	/**
	 * Function shows basic detail for the record
	 * @param <type> $request
	 */
	public function showModuleBasicView(\App\Http\Vtiger_Request $request)
	{
		return $this->showModuleDetailView($request);
	}
}
