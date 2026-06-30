<?php

/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Base\Views;

use App\Http\Vtiger_Request;

class ReferenceEditModal extends BasicModal
{
	public function checkPermission(Vtiger_Request $request): void
	{
		$recordId = (int) $request->get('record');
		$fieldName = (string) $request->get('field');
		if ($recordId <= 0 || $fieldName === '') {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $request->getModule());
		if (!$recordModel->isEditable()) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
		$fieldModel = $recordModel->getModule()->getFieldByName($fieldName);
		if (!$fieldModel || !$fieldModel->isReferenceModalEditable()) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(Vtiger_Request $request): void
	{
		$moduleName = $request->getModule();
		$recordId = (int) $request->get('record');
		$fieldName = (string) $request->get('field');
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		$fieldModel = $recordModel->getModule()->getFieldByName($fieldName);
		$fieldModel->set('fieldvalue', $recordModel->get($fieldName));

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('FIELD_MODEL', $fieldModel);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->assign('VIEW', 'Edit');

		$this->preProcess($request);
		$viewer->view('ReferenceEditModal.tpl', $moduleName);
		$this->postProcess($request);
	}

	public function validateRequest(Vtiger_Request $request): bool
	{
		return $request->validateReadAccess();
	}
}
