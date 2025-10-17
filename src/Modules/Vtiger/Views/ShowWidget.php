<?php

namespace App\Modules\Vtiger\Views;

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
class ShowWidget extends \Vtiger_Index_View
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();

		$moduleName = $request->getModule();
		$componentName = $request->get('name');
		$linkId = $request->get('linkid');
		$id = $request->get('widgetid');
		if (!empty($componentName)) {
			$className = \App\Loader::getComponentClassName('Dashboard', $componentName, $moduleName);
			if (!empty($className)) {
				$widget = NULL;
				if (!empty($linkId)) {
					$widget = new \App\Modules\Vtiger\Models\Widget();
					$widget->set('linkid', $linkId);
					$widget->set('userid', $currentUser->getId());
					$widget->set('widgetid', $id);
					$widget->set('active', $request->get('active'));
					$widget->set('filterid', $request->get('filterid', NULL));
					if ($request->has('data')) {
						$widget->set('data', $request->get('data'));
					}
					$widget->show();
				}
				$classInstance = new $className();
				$classInstance->process($request, $widget);
				return;
			}
		}

		$response = new Vtiger_Response();
		$response->setResult(array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('NO_DATA')));
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
