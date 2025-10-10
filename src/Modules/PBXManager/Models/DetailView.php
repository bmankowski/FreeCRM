<?php

namespace FreeCRM\Modules\PBXManager\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DetailView extends \FreeCRM\Modules\Vtiger\Models\DetailView
{

	/**
	 * Overrided to remove Edit button, Duplicate button
	 * To remove related links
	 */
	public function getDetailViewLinks($linkParams)
	{
		$linkTypes = array('DETAILVIEWBASIC', 'DETAILVIEW');
		$moduleModel = $this->getModule();
		$recordModel = $this->getRecord();

		$moduleName = $moduleModel->getName();
		$recordId = $recordModel->getId();

		$detailViewLink = array();

		$linkModelListDetails = \FreeCRM\Modules\Vtiger\Models\Link::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);
		//Mark all detail view basic links as detail view links.
		//Since ui will be look ugly if you need many basic links
		$detailViewBasiclinks = $linkModelListDetails['DETAILVIEWBASIC'];
		unset($linkModelListDetails['DETAILVIEWBASIC']);

		if (\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Delete', $recordId)) {
			$deletelinkModel = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => 'javascript:Vtiger_Detail_Js.deleteRecord("' . $recordModel->getDeleteUrl() . '")',
				'linkicon' => 'glyphicon glyphicon-trash',
			);
			$linkModelList['DETAILVIEW'][] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($deletelinkModel);
		}

		if (!empty($detailViewBasiclinks)) {
			foreach ($detailViewBasiclinks as $linkModel) {
				// Remove view history, needed in vtiger5 to see history but not in vtiger6
				if ($linkModel->linklabel == 'View History') {
					continue;
				}
				$linkModelList['DETAILVIEW'][] = $linkModel;
			}
		}

		$widgets = $this->getWidgets();
		if(!empty($widgets)){
			foreach ($widgets as $widgetLinkModel) {
				$linkModelList['DETAILVIEWWIDGET'][] = $widgetLinkModel;
			}
		}
		return $linkModelList;
	}
}
