<?php

namespace FreeCRM\Modules\ModComments\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use FreeCRM\Http\Vtiger_Request;
class View extends \Vtiger_Index_View
{

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$moduleName = $request->getModule();
		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($record);
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$modCommentsModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance('ModComments');

		$viewer = $this->getViewer($request);
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('COMMENT', $recordModel);
		$viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
		echo $viewer->view('Comment.tpl', $moduleName, true);
	}
}
