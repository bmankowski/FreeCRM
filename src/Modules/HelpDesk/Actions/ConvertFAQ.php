<?php

namespace App\Modules\HelpDesk\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class ConvertFAQ extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted('Faq', 'EditView');

		if (!$recordPermission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$result = array();
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName);

			$faqRecordModel = \App\Modules\Faq\Models\Record::getInstanceFromHelpDesk($recordModel);

			$answer = $faqRecordModel->get('faq_answer');
			if ($answer) {
				$faqRecordModel->save();
				header("Location: " . $faqRecordModel->getDetailViewUrl());
			} else {
				header("Location: " . $faqRecordModel->getEditViewUrl() . "&parentId=$recordId&parentModule=$moduleName");
			}
		}
	}
}
