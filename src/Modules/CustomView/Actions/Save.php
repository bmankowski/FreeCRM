<?php

namespace FreeCRM\Modules\CustomView\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Save extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($request->get('source_module'));
		$customViewModel = $this->getCVModelFromRequest($request);
		$response = new \FreeCRM\Http\Vtiger_Response();

		if (!$customViewModel->checkDuplicate()) {
			$customViewModel->save();
			$cvId = $customViewModel->getId();
			\App\Cache::delete('\FreeCRM\Modules\CustomView\Models\RecordgetInstanceById', $cvId);
			$response->setResult(array('id' => $cvId, 'listviewurl' => $moduleModel->getListViewUrl() . '&viewname=' . $cvId));
		} else {
			$response->setError(vtranslate('LBL_CUSTOM_VIEW_NAME_DUPLICATES_EXIST', $moduleName));
		}

		$response->emit();
	}

	/**
	 * Function to get the custom view model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return \FreeCRM\Modules\CustomView\Models\Record or Module specific Record Model instance
	 */
	private function getCVModelFromRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$cvId = $request->get('record');

		if (!empty($cvId)) {
			$customViewModel = \FreeCRM\Modules\CustomView\Models\Record::getInstanceById($cvId);
		} else {
			$customViewModel = \FreeCRM\Modules\CustomView\Models\Record::getCleanInstance();
			$customViewModel->setModule($request->get('source_module'));
		}
		$setmetrics = empty($request->get('setmetrics')) ? 0 : $request->get('setmetrics');
		$customViewData = array(
			'cvid' => $cvId,
			'viewname' => $request->get('viewname'),
			'setdefault' => $request->get('setdefault'),
			'setmetrics' => $setmetrics,
			'status' => $request->get('status'),
			'featured' => $request->get('featured'),
			'color' => $request->get('color'),
			'description' => $request->get('description')
		);
		$selectedColumnsList = $request->get('columnslist');
		if (empty($selectedColumnsList)) {
			$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($request->get('source_module'));
			$cvIdDefault = $moduleModel->getAllFilterCvidForModule();
			if ($cvIdDefault === false) {
				$cvId = \App\CustomView::getInstance($request->get('source_module'))->getDefaultCvId();
			}
			$defaultCustomViewModel = \FreeCRM\Modules\CustomView\Models\Record::getInstanceById($cvIdDefault);
			$selectedColumnsList = $defaultCustomViewModel->getSelectedFields();
		}
		$customViewData['columnslist'] = $selectedColumnsList;
		$stdFilterList = $request->get('stdfilterlist');
		if (!empty($stdFilterList)) {
			$customViewData['stdfilterlist'] = $stdFilterList;
		}
		$advFilterList = $request->get('advfilterlist');
		if (!empty($advFilterList)) {
			$customViewData['advfilterlist'] = $advFilterList;
		}

		return $customViewModel->setData($customViewData);
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
