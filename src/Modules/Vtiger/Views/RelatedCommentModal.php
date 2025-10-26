<?php

namespace App\Modules\Vtiger\Views;

/**
 * Update comment for related record
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class RelatedCommentModal  extends \App\Modules\Vtiger\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $record);
		if (!$recordPermission) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
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
			throw new \App\Exceptions\NoPermitted(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED'));
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RELATED_RECORD', $relatedRecord);
		$viewer->assign('RELATED_MODULE', $relatedModuleName);
		$viewer->assign('COMMENT', $rcmModel->getComment());

		$this->preProcess($request);
		$viewer->view('RelatedCommentModal.tpl', $moduleName);
		$this->postProcess($request);
	}
}
