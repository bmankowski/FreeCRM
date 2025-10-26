<?php

namespace App\Modules\Settings\Menu\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class EditMenu extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$id = $request->get('id');
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', \App\Modules\Settings\Menu\Models\Module::getInstance());
		$viewer->assign('RECORD', \App\Modules\Settings\Menu\Models\Record::getInstanceById($id));
		$viewer->assign('ICONS_LABEL', \App\Modules\Settings\Menu\Models\Record::getIcons());
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('ID', $id);
		$viewer->view('EditMenu.tpl', $qualifiedModuleName);
	}
}
