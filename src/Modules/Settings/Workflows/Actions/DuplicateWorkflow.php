<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Settings\Workflows\Actions;

class DuplicateWorkflow extends \App\Modules\Settings\Base\Actions\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModule = $request->getModule(false);
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}

		$sourceModel = \App\Modules\Settings\Workflows\Models\Record::getInstance($recordId);
		if (!$sourceModel->getWorkflowObject()) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		if ($sourceModel->isDefault()) {
			throw new \App\Exceptions\AppException(
				\App\Runtime\Vtiger_Language_Handler::translate('LBL_CANNOT_DUPLICATE_DEFAULT_WORKFLOW', $qualifiedModule)
			);
		}

		$copyModel = $sourceModel->duplicate();
		header('Location: ' . $copyModel->getEditViewUrl());
		exit;
	}
}
