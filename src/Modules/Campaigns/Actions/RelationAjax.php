<?php

namespace App\Modules\Campaigns\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class RelationAjax extends \App\Runtime\BaseActionController
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('addRelationsFromRelatedModuleViewId');
		$this->exposeMethod('updateStatus');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function to add relations using related module viewid
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function addRelationsFromRelatedModuleViewId(\App\Http\Vtiger_Request $request)
	{
		$sourceRecordId = $request->get('sourceRecord');
		$relatedModuleName = $request->get('relatedModule');
		$viewId = $request->get('viewId');
		if ($viewId) {
			$sourceModuleModel = \App\Modules\Base\Models\Module::getInstance($request->getModule());
			$relatedModuleModel = \App\Modules\Base\Models\Module::getInstance($relatedModuleName);
			$relationModel = \App\Modules\Base\Models\Relation::getInstance($sourceModuleModel, $relatedModuleModel);
			if (in_array($relatedModuleName, ['Accounts', 'Leads', 'Vendors', 'Contacts', 'Partners', 'Competition'])) {
				$queryGenerator = new \App\QueryGenerator($relatedModuleName);
				$queryGenerator->initForCustomViewById($viewId);
				$dataReader = $queryGenerator->createQuery()->createCommand()->query();
				while ($row = $dataReader->read()) {
					$relatedRecordIdsList[] = $row['id'];
				}
				if (empty($relatedRecordIdsList)) {
					$response = new \App\Http\Vtiger_Response();
					$response->setResult(array(false));
					$response->emit();
				} else {
					foreach ($relatedRecordIdsList as $relatedRecordId) {
						$relationModel->addRelation($sourceRecordId, $relatedRecordId);
					}
				}
			}
		}
	}

	/**
	 * Function to update Relation status
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function updateStatus(\App\Http\Vtiger_Request $request)
	{
		$relatedModuleName = $request->get('relatedModule');
		$relatedRecordId = $request->get('relatedRecord');
		$status = $request->get('status');
		$response = new \App\Http\Vtiger_Response();

		if ($relatedRecordId && $status && $status < 5) {
			$sourceModuleModel = \App\Modules\Base\Models\Module::getInstance($request->getModule());
			$relatedModuleModel = \App\Modules\Base\Models\Module::getInstance($relatedModuleName);

			$relationModel = \App\Modules\Base\Models\Relation::getInstance($sourceModuleModel, $relatedModuleModel);
			$relationModel->updateStatus($request->get('sourceRecord'), array($relatedRecordId => $status));

			$response->setResult(array(true));
		} else {
			$response->setError($code);
		}
		$response->emit();
	}
}
