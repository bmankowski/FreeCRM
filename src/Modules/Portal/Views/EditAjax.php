<?php

namespace FreeCRM\Modules\Portal\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class EditAjax extends View
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$viewer = $this->getViewer($request);

		if (!empty($recordId)) {
			$data = Portal_Module_Model::getRecord($recordId);

			$viewer->assign('RECORD', $recordId);
			$viewer->assign('BOOKMARK_NAME', $data['bookmarkName']);
			$viewer->assign('BOOKMARK_URL', $data['bookmarkUrl']);
		}

		$viewer->assign('MODULE', $moduleName);

		$viewer->view('EditView.tpl', $moduleName);
	}
}
