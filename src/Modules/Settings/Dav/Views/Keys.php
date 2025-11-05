<?php

namespace App\Modules\Settings\Dav\Views;
use App\Modules\Settings\DavModels\Module;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Keys extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		include 'config/api.php';
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\Dav\Models\Module::getInstance($qualifiedModuleName);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
	$viewer->assign('USERS', \App\Modules\Users\Models\Record::getAll());
	$viewer->assign('MODULE', $moduleName);
	$viewer->assign('ENABLEDAV', !in_array('dav', $enabledServices));
	
	if ($request->isAjax()) {
		$viewer->view('KeysContent.tpl', $qualifiedModuleName);
	} else {
		$viewer->view('KeysIndex.tpl', $qualifiedModuleName);
	}
	}
}
