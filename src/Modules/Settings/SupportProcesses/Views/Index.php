<?php

namespace App\Modules\Settings\SupportProcesses\Views;
use App\Modules\Settings\SupportProcessesModels\Module;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		
		\App\Log\Log::trace("Entering \App\Modules\Settings\SupportProcesses\Views\Index::process() method ...");
		$qualifiedModule = $request->getModule(false);
		$viewer = $this->getViewer($request);

		$ticketStatus = \App\Modules\Settings\SupportProcesses\Models\Module::getTicketStatus();
		$ticketStatusNotModify = \App\Modules\Settings\SupportProcesses\Models\Module::getTicketStatusNotModify();
		$viewer->assign('TICKETSTATUSNOTMODIFY', $ticketStatusNotModify);
		$viewer->assign('TICKETSTATUS', $ticketStatus);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->assign('ITEM', ['user_id' => '']);

		// Check if this is an AJAX request - if so, return only content without MainLayout
		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModule);
		} else {
			$viewer->view('Index.tpl', $qualifiedModule);
		}
		\App\Log\Log::trace("Exiting \App\Modules\Settings\SupportProcesses\Views\Index::process() method ...");
	}
}
