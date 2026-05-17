<?php

namespace App\Modules\Base\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Save extends \App\Base\Controllers\BaseActionController
{

	/**
	 * @var \App\Modules\Base\Models\Record 
	 */
	protected $record = false;

	/**
	 * @var string|null
	 */
	protected $redirectUrl;

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');

		if (!empty($record)) {
			$recordModel = $this->record ? $this->record : \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
			if (!$recordModel->isEditable()) {
				throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
		} else {
			$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
			if (!$recordModel->isCreateable()) {
				throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
			}
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
		if (\App\Http\Vtiger_Session::isImpersonated()) {
			$baseUserId = \App\Http\Vtiger_Session::getRealUserId();
			\App\Modules\Users\Models\Record::setCurrentUserId($baseUserId);
			$userModel = \App\Modules\Users\Models\Record::getInstanceById($baseUserId, 'Users');
			$request->setUser($userModel);
		}
	}

	public function preProcessAjax(\App\Http\Vtiger_Request $request)
	{
		parent::preProcessAjax($request);
		if (\App\Http\Vtiger_Session::isImpersonated()) {
			$baseUserId = \App\Http\Vtiger_Session::getRealUserId();
			\App\Modules\Users\Models\Record::setCurrentUserId($baseUserId);
			$userModel = \App\Modules\Users\Models\Record::getInstanceById($baseUserId, 'Users');
			$request->setUser($userModel);
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordModel = $this->saveRecord($request);
		if ($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentRecordId = $request->get('sourceRecord');
			$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($parentRecordId, $parentModuleName);
			$loadUrl = $parentRecordModel->getDetailViewUrl();
		} else if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$loadUrl = $recordModel->getDetailViewUrl();
		}
		$this->redirectUrl = $loadUrl;
		if ($request->get('mode') !== 'edit') {
			$request->set('record', $recordModel->getId());
		}

		if ($request->isAjax()) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult([
				'url' => $loadUrl,
				'record' => $recordModel->getId(),
			]);
			$response->emit();
			return;
		}

		header("Location: $loadUrl");
		exit;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	/**
	 * Function to save record
	 * @param \App\Http\Vtiger_Request $request - values of the record
	 * @return \App\Modules\Base\Models\Record - record Model of saved record
	 */
	public function saveRecord(\App\Http\Vtiger_Request $request)
	{
		$recordModel = $this->getRecordModelFromRequest($request);
		$recordModel->save($request);
		if ($request->get('relationOperation')) {
			$parentModuleName = $request->get('sourceModule');
			$parentModuleModel = \App\Modules\Base\Models\Module::getInstance($parentModuleName);
			$parentRecordId = $request->get('sourceRecord');
			$relatedModule = $recordModel->getModule();
			$relatedRecordId = $recordModel->getId();

			$relationModel = \App\Modules\Base\Models\Relation::getInstance($parentModuleModel, $relatedModule);
			if ($relationModel) {
				$relationModel->addRelation($parentRecordId, $relatedRecordId);
			}
		}
		if ($request->get('imgDeleted')) {
			$imageIds = $request->get('imageid');
			foreach ($imageIds as &$imageId) {
				$recordModel->deleteImage($imageId);
			}
		}
		return $recordModel;
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param \App\Http\Vtiger_Request $request
	 * @return \App\Modules\Base\Models\Record or Module specific Record Model instance
	 */
	protected function getRecordModelFromRequest(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$recordModel = $this->record ? $this->record : \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		} else {
			$recordModel = $this->record ? $this->record : \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
		}
		$fieldModelList = $recordModel->getModule()->getFields();
		foreach ($fieldModelList as $fieldName => &$fieldModel) {
			if (!$fieldModel->isWritable()) {
				continue;
			}
			if ($request->has($fieldName) && $fieldModel->get('uitype') === 300) {
				$recordModel->set($fieldName, $request->getForHtml($fieldName, null));
			} elseif ($request->has($fieldName)) {
				$recordModel->set($fieldName, $fieldModel->getUITypeModel()->getDBValue($request->get($fieldName, null), $recordModel));
			} elseif ($recordModel->isNew()) {
				$defaultValue = $fieldModel->getDefaultFieldValue();
				if ($defaultValue !== '') {
					$recordModel->set($fieldName, $defaultValue);
				}
			}
		}
		return $recordModel;
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		return $request->validateWriteAccess();
	}
}
