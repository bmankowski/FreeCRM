<?php

namespace App\Modules\Contacts\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class Edit  extends \App\Modules\Base\Views\Edit
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Assign Contacts-specific edit data
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$recordModel = $this->record;
		if (!$recordModel) {
			if (!empty($recordId)) {
				$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
			} else {
				$recordModel = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
			}
			$this->record = $recordModel;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('IMAGE_DETAILS', $recordModel->getImageDetails());

		$salutationFieldModel = \App\Modules\Base\Models\Field::getInstance('salutationtype', $recordModel->getModule());
		// Fix for http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7851
		$salutationType = $request->get('salutationtype');
		if (!empty($salutationType)) {
			$salutationFieldModel->set('fieldvalue', $request->get('salutationtype'));
		} else {
			$salutationFieldModel->set('fieldvalue', $recordModel->get('salutationtype'));
		}
		$viewer->assign('SALUTATION_FIELD_MODEL', $salutationFieldModel);
	}

}
