<?php

namespace FreeCRM\Modules\Settings\HideBlocks\Models;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

use FreeCRM\Modules\Vtiger\Models\ListView as Vtiger_ListView_Model;
class ListView extends \Settings_Vtiger_ListView_Model
{

	public function getBasicListQuery()
	{
		$query = parent::getBasicListQuery();
		$query->innerJoin('vtiger_blocks', 'vtiger_blocks.blockid = vtiger_blocks_hide.blockid');
		$query->innerJoin('vtiger_tab', 'vtiger_tab.tabid = vtiger_blocks.tabid');
		return $query;
	}
}
