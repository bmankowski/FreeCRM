<?php

namespace App\Modules\Settings\FinancialProcesses\Views;


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
		
		\App\Log::trace("Entering \App\Modules\Settings\FinancialProcesses\Views\Index::process() method ...");
		$qualifiedModule = $request->getModule(false);
		$viewer = $this->getViewer($request);

		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));

		// Add AJAX detection for MainLayout conversion
		if ($request->isAjax()) {
			// AJAX request - return content only
			$viewer->view('IndexContent.tpl', $qualifiedModule);
		} else {
			// Initial page load - return full page with MainLayout
			$viewer->view('Index.tpl', $qualifiedModule);
		}
		\App\Log::trace("Exiting \App\Modules\Settings\FinancialProcesses\Views\Index::process() method ...");
	}
}
