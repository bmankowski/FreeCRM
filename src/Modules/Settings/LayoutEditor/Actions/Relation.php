<?php

namespace FreeCRM\Modules\Settings\LayoutEditor\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

use FreeCRM\Modules\Vtiger\Models\Relation as Vtiger_Relation_Model;
class Relation extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function __construct()
	{
		$this->exposeMethod('changeStatusRelation');
		$this->exposeMethod('updateSequenceRelatedModule');
		$this->exposeMethod('updateSelectedFields');
		$this->exposeMethod('updateStateFavorites');
		$this->exposeMethod('addRelation');
		$this->exposeMethod('removeRelation');
	}

	public function changeStatusRelation(\FreeCRM\Http\Vtiger_Request $request)
	{
		$relationId = $request->get('relationId');
		$status = $request->get('status');
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			\Vtiger_Relation_Model::updateRelationPresence($relationId, $status);
			$response->setResult(array('success' => true));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function updateSequenceRelatedModule(\FreeCRM\Http\Vtiger_Request $request)
	{
		$modules = $request->get('modules');
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			\Vtiger_Relation_Model::updateRelationSequence($modules);
			$response->setResult(array('success' => true));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function updateSelectedFields(\FreeCRM\Http\Vtiger_Request $request)
	{
		$fields = $request->get('fields');
		$relationId = $request->get('relationId');
		$isInventory = $request->get('inventory');
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			if ($isInventory) {
				\Vtiger_Relation_Model::updateModuleRelatedInventoryFields($relationId, $fields);
			} else {
				\Vtiger_Relation_Model::updateModuleRelatedFields($relationId, $fields);
			}
			$response->setResult(array('success' => true));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function addRelation(\FreeCRM\Http\Vtiger_Request $request)
	{
		$source = $request->get('source');
		$target = $request->get('target');
		$label = $request->get('label');
		$type = $request->get('type');
		$actions = is_array($request->get('actions')) ? $request->get('actions') : [$request->get('actions')];

		$source_Module = vtlib\Module::getInstance($source);
		$moduleInstance = vtlib\Module::getInstance($target);
		$source_Module->setRelatedList($moduleInstance, $label, $actions, $type);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->emit();
	}

	public function removeRelation(\FreeCRM\Http\Vtiger_Request $request)
	{
		$relationId = $request->get('relationId');
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			\Vtiger_Relation_Model::removeRelationById($relationId);
			$response->setResult(['success' => true]);
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function updateStateFavorites(\FreeCRM\Http\Vtiger_Request $request)
	{
		$relationId = $request->get('relationId');
		$status = $request->get('status');
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			\Vtiger_Relation_Model::updateStateFavorites($relationId, $status);
			$response->setResult(array('success' => true));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
