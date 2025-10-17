<?php

namespace FreeCRM\Modules\Vtiger\Actions;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

use FreeCRM\Http\Vtiger_Request;

class RemoveWidget extends \Vtiger_Index_View
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$linkId = $request->get('linkid');
		$response = new \FreeCRM\Http\Vtiger_Response();

		if ($request->has('widgetid')) {
			$widget = \FreeCRM\Modules\Vtiger\Models\Widget::getInstanceWithWidgetId($request->get('widgetid'), $currentUser->getId());
		} else {
			$widget = \FreeCRM\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		}

		if (!$widget->isDefault()) {
			$widget->remove('hide');
			$response->setResult(['linkid' => $linkId,
				'name' => $widget->getName(),
				'url' => $widget->getUrl(),
				'title' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate($widget->getTitle(), $request->getModule()),
				'id' => $widget->get('id'),
				'deleteFromList' => $widget->get('deleteFromList')
			]);
		} else {
			$response->setError(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_CAN_NOT_REMOVE_DEFAULT_WIDGET', $moduleName));
		}
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
