<?php

namespace App\Modules\Settings\PublicHoliday\Views;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Configuration extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		
		\App\Log::trace("Entering \App\Modules\Settings\PublicHoliday\Views\Configuration::process() method ...");
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$date = $request->get('date');
		if (!$date) {
			$startDate = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
			$startDate = new \App\Fields\DateTimeField($startDate);
			$endDate = date('Y-m-d', mktime(23, 59, 59, date('m') + 1, 0, date('Y')));
			$endDate = new \App\Fields\DateTimeField($endDate);
			$date = [
				$startDate->getDisplayDate(),
				$endDate->getDisplayDate(),
			];
		}
		$holidays = \App\Modules\Settings\PublicHoliday\Models\Module::getHolidays($date);
		$viewer->assign('DATE', implode(" - ", $date));
		$viewer->assign('HOLIDAYS', $holidays);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		
		// Prepare PublicHoliday ConfigurationContent-specific data for ConfigurationContent template
		$this->preparePublicHolidayConfigurationData($viewer, $holidays, $currentUser);

		if ($request->isAjax()) {
			// AJAX request - return content only
			$viewer->view('ConfigurationContent.tpl', $request->getModule(false));
		} else {
			// Initial page load - return full page with MainLayout
			$viewer->view('Configuration.tpl', $request->getModule(false));
		}
		\App\Log::trace("Exiting \App\Modules\Settings\PublicHoliday\Views\Configuration::process() method ...");
	}
	
	/**
	 * Prepare data for PublicHoliday ConfigurationContent template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function preparePublicHolidayConfigurationData($viewer, $holidays, $currentUser)
	{
		// Prepare formatted dates for holidays
		$holidayDates = [];
		foreach ($holidays as $holiday) {
			$holidayDates[$holiday['id']] = \App\Fields\DateTime::currentUserDisplayDate($holiday['date']);
		}
		$viewer->assign('HOLIDAY_DATES', $holidayDates);
		
		// Prepare current date formatted
		$viewer->assign('CURRENT_DATE_FORMATTED', \App\Fields\DateTime::currentUserDisplayDate(date('Y-m-d')));
	}
}
