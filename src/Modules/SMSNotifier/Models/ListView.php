<?php

namespace FreeCRM\Modules\SMSNotifier\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class ListView extends \FreeCRM\Modules\Vtiger\Models\ListView
{

	public function getAdvancedLinks()
	{
		$moduleModel = $this->getModule();
		$createPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'CreateView');
		$advancedLinks = [];

		$exportPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'Export');
		if ($exportPermission) {
			$advancedLinks[] = array(
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_EXPORT',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerExportAction("' . $this->getModule()->getExportUrl() . '")',
				'linkicon' => ''
			);
		}

		if (!\FreeCRM\Modules\Settings\ModuleManager\Models\Library::checkLibrary('mPDF') && \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'ExportPdf')) {
			$handlerClass = \FreeCRM\Loader::getComponentClassName('Model', 'PDF', $moduleModel->getName());
			$pdfModel = new $handlerClass();
			$templates = $pdfModel->getActiveTemplatesForModule($moduleModel->getName(), 'List');
			if (count($templates) > 0) {
				$advancedLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_PDF'),
					'linkurl' => 'javascript:Vtiger_Header_Js.getInstance().showPdfModal("index.php?module=' . $moduleModel->getName() . '&view=PDF&fromview=List");',
					'linkicon' => 'glyphicon glyphicon-save-file',
					'title' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_PDF')
				];
			}
		}

		$quickExportToExcelPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'QuickExportToExcel');
		if ($quickExportToExcelPermission && !\FreeCRM\Modules\Settings\ModuleManager\Models\Library::checkLibrary('PHPExcel')) {
			$advancedLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_QUICK_EXPORT_TO_EXCEL',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerQuickExportToExcel("' . $moduleModel->getName() . '")',
				'linkicon' => ''
			);
		}
		return $advancedLinks;
	}
	/*
	 * Function to get Basic links
	 * @return array of Basic links
	 */

	public function getBasicLinks()
	{
		$basicLinks = [];
		$moduleModel = $this->getModule();
		if (!\FreeCRM\Modules\Settings\ModuleManager\Models\Library::checkLibrary('mPDF') && \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'ExportPdf')) {
			$handlerClass = \FreeCRM\Loader::getComponentClassName('Model', 'PDF', $moduleModel->getName());
			$pdfModel = new $handlerClass();
			$templates = $pdfModel->getActiveTemplatesForModule($moduleModel->getName(), 'List');
			if (count($templates) > 0) {
				$basicLinks[] = [
					'linktype' => 'LISTVIEWBASIC',
					'linkurl' => 'javascript:Vtiger_Header_Js.getInstance().showPdfModal("index.php?module=' . $moduleModel->getName() . '&view=PDF&fromview=List");',
					'linkicon' => 'glyphicon glyphicon-save-file',
					'title' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_PDF')
				];
			}
		}
		return $basicLinks;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  \FreeCRM\Modules\Vtiger\Models\Link instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$moduleModel = $this->getModule();
		$moduleName = $moduleModel->getName();

		$linkTypes = array('LISTVIEWMASSACTION');
		$links = \FreeCRM\Modules\Vtiger\Models\Link::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);

		$massActionLink = array();
		if ($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'Delete')) {
			$massActionLink = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_DELETE', $moduleName),
				'linkurl' => 'javascript:Vtiger_List_Js.massDeleteRecords("index.php?module=' . $moduleName . '&action=MassDelete");',
				'linkicon' => ''
			);
		}
		$links['LISTVIEWMASSACTION'][] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($massActionLink);

		return $links;
	}
}
