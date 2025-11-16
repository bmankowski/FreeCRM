<?php

namespace App\Modules\ModComments\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


class DetailAjax extends \App\Modules\Base\Views\Index
{

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$moduleName = $request->getModule();
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record);
		$currentUserModel = $request->getUser();
		$modCommentsModel = \App\Modules\Base\Models\Module::getInstance('ModComments');

		$viewer = $this->getViewer($request);
		$relatedTo = $recordModel->get('related_to');
		$relatedModule = $relatedTo ? \App\Records\Record::getType($relatedTo) : null;
		$viewer->assign('CURRENTUSER', $currentUserModel);
		$viewer->assign('COMMENT', $recordModel);
		$viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
		$viewer->assign('RELATED_MODULE', $relatedModule);
		echo $viewer->view('Comment.tpl', $moduleName, true);
	}
}
