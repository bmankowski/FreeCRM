<?php

namespace App\Modules\Vtiger\Actions;

/**
 * Update comment for related record
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class RelatedCommentModal extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $record);
		if (!$recordPermission) {
			throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$relatedRecord = $request->get('relid');
		$relatedModuleName = $request->get('relmodule');

		$rcmModel = \App\Modules\Vtiger\Models\RelatedCommentModal::getInstance($record, $moduleName, $relatedRecord, $relatedModuleName);
		if (!$rcmModel->isEditable()) {
			throw new \Exception\NoPermitted(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED'));
		}
		$rcmModel->save($request->get('comment'));

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(\App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVED_RELATION_COMMENT', $moduleName));
		$response->emit();
	}
}
