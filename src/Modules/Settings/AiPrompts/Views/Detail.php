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

namespace App\Modules\Settings\AiPrompts\Views;

use App\Ai\Prompt\ActionRegistry;

class Detail extends \App\Modules\Settings\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser() || empty($request->get('record'))) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$record = (int) $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = \App\Modules\Settings\AiPrompts\Models\Record::getInstanceById($record);
		if ($recordModel === null) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}

		$actionKey = (string) $recordModel->get('action_key');
		$placeholders = ActionRegistry::isKnown($actionKey)
			? ActionRegistry::placeholders($actionKey)
			: [];

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->assign('PLACEHOLDERS', $placeholders);
		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}
}
