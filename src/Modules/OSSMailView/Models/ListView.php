<?php

namespace App\Modules\OSSMailView\Models;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class ListView extends \App\Modules\Base\Models\ListView
{

	public function getBasicLinks()
	{
		$basicLinks = array();
		$moduleModel = $this->getModule();
		$createPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'CreateView');
		if ($createPermission && \App\Core\AppConfig::main('isActiveSendingMails') && \App\Modules\Users\Models\Privileges::isPermitted('OSSMail')) {
			$basicLinks[] = array(
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_CREATEMAIL',
				'linkurl' => "javascript:window.location='index.php?module=OSSMail&view=compose'",
				'linkclass' => 'moduleColor_' . $moduleModel->getName(),
				'linkicon' => 'glyphicon glyphicon-plus',
				'showLabel' => 1,
			);
		}
		return $basicLinks;
	}

	public function getListViewMassActions($linkParams)
	{
		$currentUserModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$moduleModel = $this->getModule();
		$massActionLinks = [];

		if ($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'MassDelete')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_MASS_DELETE',
				'linkurl' => 'javascript:Vtiger_ListView_Js.massDeleteRecords("index.php?module=' . $moduleModel->get('name') . '&action=MassDelete");',
				'linkicon' => ''
			);
		}

		if ($currentUserModel->hasModuleActionPermission($moduleModel->getId(), 'EditView')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_BindMails',
				'linkurl' => 'javascript:OSSMailView_ListView_Js.bindMails("index.php?module=' . $moduleModel->get('name') . '&action=BindMails")',
				'linkicon' => ''
			);
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_ChangeType',
				'linkurl' => 'javascript:OSSMailView_ListView_Js.triggerChangeType("index.php?module=' . $moduleModel->get('name') . '&view=ChangeType")',
				'linkicon' => ''
			);
		}
		foreach ($massActionLinks as $massActionLink) {
			$links['LISTVIEWMASSACTION'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($massActionLink);
		}
		return $links;
	}
}
