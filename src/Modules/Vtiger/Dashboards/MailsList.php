<?php

namespace App\Modules\Vtiger\Dashboards;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************************************************************** */

use App\Http\Vtiger_Request;

class MailsList  extends \App\Modules\Vtiger\Views\Index
{

	public function process(Vtiger_Request $request, $widget = NULL)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$user = $request->get('user');
		$linkId = $request->get('linkid');
		$data = $request->getAll();
		$widget = \App\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('USER', $user);
		$viewer->assign('ACCOUNTSLIST', \App\Modules\OSSMail\Models\Record::getAccountsList(false, true));
		$viewer->assign('DATA', $data);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/MailsListContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/MailsList.tpl', $moduleName);
		}
	}
}
