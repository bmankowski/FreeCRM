<?php

namespace FreeCRM\Modules\Users\Models;

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

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of \FreeCRM\Modules\Vtiger\Models\Link instances
	 */
	public function getListViewLinks($linkParams)
	{
		$linkTypes = array('LISTVIEWBASIC', 'LISTVIEW', 'LISTVIEWSETTING');
		$links = \FreeCRM\Modules\Vtiger\Models\Link::getAllByType($this->getModule()->getId(), $linkTypes, $linkParams);

		$basicLinks = array(
			array(
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_ADD_RECORD',
				'linkurl' => $this->getModule()->getCreateRecordUrl(),
				'linkicon' => ''
			)
		);
		foreach ($basicLinks as $basicLink) {
			$links['LISTVIEWBASIC'][] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($basicLink);
		}

		$advancedLinks = $this->getAdvancedLinks();
		foreach ($advancedLinks as $advancedLink) {
			$links['LISTVIEW'][] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($advancedLink);
		}
		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  \FreeCRM\Modules\Vtiger\Models\Link instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams)
	{
		$links = parent::getListViewMassActions($linkParams);
		$privilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();

		$massActionLinks = [];
		if ($linkParams['MODULE'] == 'Users' && $linkParams['ACTION'] == 'List' && \vtlib\Functions::userIsAdministrator($privilegesModel)) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_MASS_PWD_EDIT',
				'linkurl' => 'javascript:Settings_Users_List_Js.triggerEditPasswords("index.php?module=Users&view=EditAjax&mode=editPasswords", "' . $linkParams['MODULE'] . '")',
				'linkicon' => ''
			);
		}
		foreach ($massActionLinks as $massActionLink) {
			$links['LISTVIEWMASSACTION'][] = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues($massActionLink);
		}
		$countLinks = count($links['LISTVIEWMASSACTION']);
		for ($i = 0; $i < $countLinks; $i++) {
			if ($links['LISTVIEWMASSACTION'][$i]->linklabel === 'LBL_MASS_DELETE' || $links['LISTVIEWMASSACTION'][$i]->linklabel === 'LBL_TRANSFER_OWNERSHIP') {
				unset($links['LISTVIEWMASSACTION'][$i]);
			}
		}

		return $links;
	}

	/**
	 * Load list view conditions
	 */
	public function loadListViewCondition()
	{
		$searchKey = $this->get('search_key');
		if ($searchKey && $searchKey === 'status') {
			$this->get('query_generator')->deletedCondition = false;
		}
		parent::loadListViewCondition();
	}

	/**
	 * Function to get the list view entries
	 * @param \FreeCRM\Modules\Vtiger\Models\Paging $pagingModel, $status (Active or Inactive User). Default false
	 * @return <Array> - Associative array of record id mapped to \FreeCRM\Modules\Vtiger\Models\Record instance.
	 */
	public function getListViewEntries(\FreeCRM\Modules\Vtiger\Models\Paging $pagingModel)
	{
		$queryGenerator = $this->get('query_generator');
		// Added as Users module do not have custom filters and id column is added by querygenerator.
		$fields = $queryGenerator->getFields();
		$fields[] = 'id';
		$queryGenerator->setFields($fields);
		$searchParams = $this->get('search_params');
		if (empty($searchParams)) {
			$searchParams = [];
		} else {
			foreach ($searchParams as &$params) {
				foreach ($params as &$param) {
					if (strpos($param['columnname'], 'is_admin') !== false) {
						$param['value'] = $param['value'] == '0' ? 'off' : 'on';
					}
				}
			}
		}
		$this->set('search_params', $searchParams);
		return parent::getListViewEntries($pagingModel);
	}

	/**
	 * Function to get the list view header
	 * @return \FreeCRM\Modules\Vtiger\Models\Field[] - List of \FreeCRM\Modules\Vtiger\Models\Field instances
	 */
	public function getListViewHeaders()
	{
		$headerFieldModels = [];
		$headerFields = $this->getQueryGenerator()->getListViewFields();
		foreach ($headerFields as $fieldName => &$fieldsModel) {
			if ($fieldsModel && ((!$fieldsModel->isViewable() && $fieldsModel->getUitype() !== 106) || !$fieldsModel->getPermissions())) {
				continue;
			}
			$headerFieldModels[$fieldName] = $fieldsModel;
		}
		return $headerFieldModels;
	}

	public function getListViewCount()
	{
		$searchParams = $this->get('search_params');
		if (is_array($searchParams) && count($searchParams[0]['columns']) < 1) {
			$this->set('search_params', []);
		}
		return parent::getListViewCount();
	}
	/*
	 * Function to give advance links of Users module
	 * @return array of advanced links
	 */

	public function getAdvancedLinks()
	{
		$moduleModel = $this->getModule();
		$createPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'CreateView');
		$advancedLinks = array();
		$importPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'Import');
		if ($importPermission && $createPermission) {
			$advancedLinks[] = array(
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_IMPORT',
				'linkurl' => $moduleModel->getImportUrl(),
				'linkicon' => ''
			);
			$advancedLinks[] = array(
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_EXPORT',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerExportAction("' . $moduleModel->getExportUrl() . '")',
				'linkicon' => ''
			);
		}

		return $advancedLinks;
	}
}
