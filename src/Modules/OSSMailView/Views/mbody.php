<?php
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class OSSMailView_mbody_View extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $recordId);
		if (!$recordPermission) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $moduleName);
		$content = $recordModel->get('content');
		CSRF::$frameBreaker = false;
		CSRF::$rewriteJs = null;

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULENAME', $moduleName);
		$viewer->assign('CONTENT', \vtlib\Functions::getHtmlOrPlainText($content));
		$viewer->assign('RECORD', $record);
		$viewer->view('mbody.tpl', 'OSSMailView');
	}
}
