<?php

namespace App\Modules\Base\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use App\Http\Vtiger_Request;

class SaveWidgetPositions  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();

		$positionsMap = $request->get('positionsmap');

		if ($positionsMap) {
			foreach ($positionsMap as $id => $position) {
				list ($linkid, $widgetid) = explode('-', $id);
				if ($widgetid) {
					\App\Modules\Base\Models\Widget::updateWidgetPosition($position, NULL, $widgetid, $currentUser->getId());
				} else {
					\App\Modules\Base\Models\Widget::updateWidgetPosition($position, $linkid, NULL, $currentUser->getId());
				}
			}
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array('Save' => 'OK'));
		$response->emit();
	}
}
