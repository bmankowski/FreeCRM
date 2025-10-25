<?php

namespace App\Modules\Vtiger\Actions;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

use App\Http\Vtiger_Request;

class RemoveWidget  extends \App\Modules\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		$linkId = $request->get('linkid');
		$response = new \App\Http\Vtiger_Response();

		if ($request->has('widgetid')) {
			$widget = \App\Modules\Vtiger\Models\Widget::getInstanceWithWidgetId($request->get('widgetid'), $currentUser->getId());
		} else {
			$widget = \App\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		}

		if (!$widget->isDefault()) {
			$widget->remove('hide');
			$response->setResult(['linkid' => $linkId,
				'name' => $widget->getName(),
				'url' => $widget->getUrl(),
				'title' => \App\Runtime\Vtiger_Language_Handler::translate($widget->getTitle(), $request->getModule()),
				'id' => $widget->get('id'),
				'deleteFromList' => $widget->get('deleteFromList')
			]);
		} else {
			$response->setError(\App\Runtime\Vtiger_Language_Handler::translate('LBL_CAN_NOT_REMOVE_DEFAULT_WIDGET', $moduleName));
		}
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
