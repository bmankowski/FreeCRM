<?php

namespace FreeCRM\Modules\Vtiger\Widgets;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Updates extends \FreeCRM\Modules\Vtiger\Widgets\Basic
{

	public function getUrl()
	{
		return 'module=' . $this->Module . '&view=Detail&record=' . $this->Record . '&mode=showRecentActivities&page=1&limit=5&skipHeader=true';
	}

	public function getWidget()
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$moduelName = 'ModTracker';
		$this->Config['tpl'] = 'Updates.tpl';
		$this->Config['moduleBaseName'] = $moduelName;
		$this->Config['url'] = $this->getUrl();
		$this->Config['newChanege'] = \FreeCRM\Modules\ModTracker\Models\Record::isNewChange($this->Record, $currentUser->getRealId());
		$this->Config['switchHeader'] = [];
		$this->Config['switchHeader']['on'] = 'changes';
		$this->Config['switchHeader']['off'] = 'review';
		$this->Config['switchHeaderLables']['on'] = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_UPDATES', $moduelName);
		$this->Config['switchHeaderLables']['off'] = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_REVIEW_HISTORY', $moduelName);
		return $this->Config;
	}
}
