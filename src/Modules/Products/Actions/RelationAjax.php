<?php

namespace App\Modules\Products\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class RelationAjax extends \App\Base\Controllers\BaseActionController
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('addListPrice');
	}

	public function process(\App\Http\Vtiger_Request $request)
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
			$relatedModule = \App\Utils\ModuleUtils::getModuleName($relatedModule);
		}
		$relatedRecordIdList = $request->get('related_record_list');
		$sourceModuleModel = \App\Modules\Base\Models\Module::getInstance($sourceModule);
		$relatedModuleModel = \App\Modules\Base\Models\Module::getInstance($relatedModule);
		$relationModel = \App\Modules\Base\Models\Relation::getInstance($sourceModuleModel, $relatedModuleModel);
		foreach ($relatedRecordIdList as $relatedRecordId) {
			$relationModel->addRelation($sourceRecordId, $relatedRecordId, $listPrice);
			if ($relatedModule == 'PriceBooks') {
				$recordModel = \App\Modules\Base\Models\Record::getInstanceById($relatedRecordId);
				if ($sourceRecordId && ($sourceModule === 'Products' || $sourceModule === 'Services')) {
					$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($sourceRecordId, $sourceModule);
					$recordModel->updateListPrice($sourceRecordId, $parentRecordModel->get('unit_price'));
				}
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	/**
	 * Function adds Products/Services-PriceBooks Relation
	 * @param mixed $request
	 */
	public function addListPrice($request)
	{
		$sourceModule = $request->getModule();
		$sourceRecordId = $request->get('src_record');
		$relatedModule = $request->get('related_module');
		$relInfos = $request->get('relinfo');

		$sourceModuleModel = \App\Modules\Base\Models\Module::getInstance($sourceModule);
		$relatedModuleModel = \App\Modules\Base\Models\Module::getInstance($relatedModule);
		$relationModel = \App\Modules\Base\Models\Relation::getInstance($sourceModuleModel, $relatedModuleModel);
		foreach ($relInfos as $relInfo) {
			$price = \App\Fields\CurrencyField::convertToDBFormat($relInfo['price'], null, true);
			$relationModel->addListPrice($sourceRecordId, $relInfo['id'], $price);
		}
	}
}

?>
