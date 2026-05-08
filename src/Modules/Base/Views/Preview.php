<?php

namespace App\Modules\Base\Views;

use App\Http\Vtiger_Request;

class Preview extends \App\Modules\Base\Views\Index
{
	/**
	 * Checking permissions
	 *
	 * @param Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermittedToRecord
	 */
	public function checkPermission(Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		if (!is_numeric($recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		if (!\App\Modules\Users\Models\Privileges::isPermitted($request->getModule(), 'DetailView', (int) $recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function process(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$handlerClass = \App\Core\Loader::getComponentClassName('View', 'Detail', $moduleName);
		$detailView = new $handlerClass();
		$request->set('isReadOnly', true);
		echo $detailView->showModuleSummaryView($request);
	}
}

