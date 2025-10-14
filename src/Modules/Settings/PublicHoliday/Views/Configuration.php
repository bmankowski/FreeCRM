<?php

namespace FreeCRM\Modules\Settings\PublicHoliday\Views;
use FreeCRM\Modules\Settings\PublicHolidayModels\Module;
use FreeCRM\Modules\Settings\PublicHolidayViews\Configuration;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Configuration extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		
		\App\Log::trace("Entering \FreeCRM\Modules\Settings\PublicHoliday\Views\Configuration::process() method ...");
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$date = $request->get('date');
		if (!$date) {
			$startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
			$startDate = new DateTimeField($startDate);
			$endDate = date('Y-m-d', mktime(23, 59, 59, date('m') + 1, 0, date('Y')));
			$endDate = new DateTimeField($endDate);
			$date = [
				$startDate->getDisplayDate(),
				$endDate->getDisplayDate(),
			];
		}
		$holidays = \FreeCRM\Modules\Settings\PublicHoliday\Models\Module::getHolidays($date);
		$viewer->assign('DATE', implode(" - ", $date));
		$viewer->assign('HOLIDAYS', $holidays);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));

		echo $viewer->view('Configuration.tpl', $request->getModule(false), true);
		\App\Log::trace("Exiting \FreeCRM\Modules\Settings\PublicHoliday\Views\Configuration::process() method ...");
	}
}
