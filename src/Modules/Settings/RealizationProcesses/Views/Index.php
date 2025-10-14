<?php

namespace FreeCRM\Modules\Settings\RealizationProcesses\Views;
use FreeCRM\Modules\Settings\RealizationProcessesModels\Module;
use FreeCRM\Modules\Settings\RealizationProcessesViews\Index;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Index extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		
		\App\Log::trace("Entering \FreeCRM\Modules\Settings\RealizationProcesses\Views\Index::process() method ...");
		$qualifiedModule = $request->getModule(false);
		$viewer = $this->getViewer($request);

		$projectStatus = \FreeCRM\Modules\Settings\RealizationProcesses\Models\Module::getProjectStatus();
		$statusNotModify = \FreeCRM\Modules\Settings\RealizationProcesses\Models\Module::getStatusNotModify();
		$viewer->assign('STATUS_NOT_MODIFY', $statusNotModify);
		$viewer->assign('PROJECT_STATUS', $projectStatus);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));

		$viewer->view('Index.tpl', $qualifiedModule);
		\App\Log::trace("Exiting \FreeCRM\Modules\Settings\RealizationProcesses\Views\Index::process() method ...");
	}
}
