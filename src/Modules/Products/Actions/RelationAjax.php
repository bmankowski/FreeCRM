<?php

namespace FreeCRM\Modules\Products\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class RelationAjax extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('addListPrice');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}
	/*
	 * Function to add relation for specified source record id and related record id list
	 * @param <array> $request
	 */

	public function addRelation($request)
	{
		$sourceModule = $request->getModule();
		$sourceRecordId = $request->get('src_record');

		$relatedModule = $request->get('related_module');
		if (is_numeric($relatedModule)) {
			$relatedModule = \vtlib\Functions::getModuleName($relatedModule);
		}
		$relatedRecordIdList = $request->get('related_record_list');
		$sourceModuleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($sourceModule);
		$relatedModuleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($relatedModule);
		$relationModel = \FreeCRM\Modules\Vtiger\Models\Relation::getInstance($sourceModuleModel, $relatedModuleModel);
		foreach ($relatedRecordIdList as $relatedRecordId) {
			$relationModel->addRelation($sourceRecordId, $relatedRecordId, $listPrice);
			if ($relatedModule == 'PriceBooks') {
				$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($relatedRecordId);
				if ($sourceRecordId && ($sourceModule === 'Products' || $sourceModule === 'Services')) {
					$parentRecordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($sourceRecordId, $sourceModule);
					$recordModel->updateListPrice($sourceRecordId, $parentRecordModel->get('unit_price'));
				}
			}
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	/**
	 * Function adds Products/Services-PriceBooks Relation
	 * @param type $request
	 */
	public function addListPrice($request)
	{
		$sourceModule = $request->getModule();
		$sourceRecordId = $request->get('src_record');
		$relatedModule = $request->get('related_module');
		$relInfos = $request->get('relinfo');

		$sourceModuleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($sourceModule);
		$relatedModuleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($relatedModule);
		$relationModel = \FreeCRM\Modules\Vtiger\Models\Relation::getInstance($sourceModuleModel, $relatedModuleModel);
		foreach ($relInfos as $relInfo) {
			$price = CurrencyField::convertToDBFormat($relInfo['price'], null, true);
			$relationModel->addListPrice($sourceRecordId, $relInfo['id'], $price);
		}
	}
}

?>
