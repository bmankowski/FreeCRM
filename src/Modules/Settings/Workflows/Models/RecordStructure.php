<?php

namespace FreeCRM\Modules\Settings\Workflows\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class RecordStructure extends \Vtiger_RecordStructure_Model
{

	const RECORD_STRUCTURE_MODE_DEFAULT = '';
	const RECORD_STRUCTURE_MODE_FILTER = 'Filter';

	public function setWorkFlowModel($workFlowModel)
	{
		$this->workFlowModel = $workFlowModel;
	}

	public function getWorkFlowModel()
	{
		return $this->workFlowModel;
	}

	public static function getInstanceForWorkFlowModule($workFlowModel, $mode)
	{
		$className = \FreeCRM\Vtiger_Loader::getComponentClassName('Model', $mode . 'RecordStructure', 'Settings:Workflows');
		$instance = new $className();
		$instance->setWorkFlowModel($workFlowModel);
		$instance->setModule($workFlowModel->getModule());
		return $instance;
	}
}
