<?php


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * ************************************************************************************************************************************/


 namespace FreeCRM\Modules\Settings\HideBlocks\Views;


use FreeCRM\Modules\Vtiger\Views\List as Vtiger_List_View;
use FreeCRM\Http\Vtiger_Request;


class List extends Vtiger_List_View
{

	public function preProcess(Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		parent::preProcess($request, false);
	}
}
