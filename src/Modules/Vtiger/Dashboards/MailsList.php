<?php

namespace FreeCRM\Modules\Vtiger\Dashboards;

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

use FreeCRM\Http\Vtiger_Request;

class MailsList extends \Vtiger_Index_View
{

	public function process(Vtiger_Request $request, $widget = NULL)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$user = $request->get('user');
		$linkId = $request->get('linkid');
		$data = $request->getAll();
		$widget = \FreeCRM\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('USER', $user);
		$viewer->assign('ACCOUNTSLIST', \FreeCRM\Modules\OSSMail\Models\Record::getAccountsList(false, true));
		$viewer->assign('DATA', $data);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/MailsListContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/MailsList.tpl', $moduleName);
		}
	}
}
