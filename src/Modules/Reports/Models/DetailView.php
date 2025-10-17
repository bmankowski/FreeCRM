<?php

namespace FreeCRM\Modules\Reports\Models;

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
	 * Function to get the instance
	 * @param string $moduleName - module name
	 * @param string $recordId - record id
	 * @return <\FreeCRM\Modules\Vtiger\Models\DetailView>
	 */
	public static function getInstance($moduleName, $recordId)
	{
		$modelClassName = \FreeCRM\Loader::getComponentClassName('Model', 'DetailView', $moduleName);
		$instance = new $modelClassName();

		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$recordModel = \FreeCRM\Modules\Reports\Models\Record::getCleanInstance($recordId, $moduleName);

		return $instance->setModule($moduleModel)->setRecord($recordModel);
	}

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param <array> $linkParams - parameters which will be used to calicaulate the params
	 * @return <array> - array of link models in the format as below
	 *                   array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams = '')
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();

		$moduleModel = $this->getModule();
		$recordModel = $this->getRecord();
		$moduleName = $moduleModel->getName();

		$detailViewLinks = array();
		$printPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'Print');
		if ($printPermission) {
			$detailViewLinks[] = array(
				'linklabel' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_REPORT_PRINT', $moduleName),
				'linkurl' => $recordModel->getReportPrintURL(),
				'linkicon' => 'fa fa-print'
			);
		}

		$exportPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'Export');
		if ($exportPermission) {
			$detailViewLinks[] = array(
				'linklabel' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_REPORT_CSV', $moduleName),
				'linkurl' => $recordModel->getReportCSVURL(),
				'linkicon' => 'fa fa-file-text-o'
			);

			if (!\FreeCRM\Modules\Settings\ModuleManager\Models\Library::checkLibrary('PHPExcel')) {
				$detailViewLinks[] = array(
					'linklabel' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_REPORT_EXPORT_EXCEL', $moduleName),
					'linkurl' => $recordModel->getReportExcelURL(),
					'linkicon' => 'fa fa-file-excel-o'
				);
			}
		}

		$linkModelList = array();
		foreach ($detailViewLinks as $detailViewLinkEntry) {
			$linkModelList[] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($detailViewLinkEntry);
		}

		return $linkModelList;
	}

	/**
	 * Function to get the detail view widgets
	 * @return <Array> - List of widgets , where each widget is an Vtiger_Link_Model
	 */
	public function getWidgets()
	{
		$moduleModel = $this->getModule();
		$widgets = array();

		if ($moduleModel->isTrackingEnabled()) {
			$widgets[] = array(
				'linktype' => 'DETAILVIEWWIDGET',
				'linklabel' => 'LBL_RECENT_ACTIVITIES',
				'linkurl' => 'module=' . $this->getModuleName() . '&view=Detail&record=' . $this->getRecord()->getId() .
				'&mode=showRecentActivities&page=1&limit=5',
			);
		}

		$widgetLinks = array();
		foreach ($widgets as $widgetDetails) {
			$widgetLinks[] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($widgetDetails);
		}
		return $widgetLinks;
	}
}
