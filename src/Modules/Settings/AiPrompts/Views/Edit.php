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

class Edit extends \App\Modules\Settings\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$moduleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$record = $request->get('record');
		$recordId = '';
		if (!empty($record)) {
			$recordModel = \App\Modules\Settings\AiPrompts\Models\Record::getInstanceById((int) $record);
			if ($recordModel === null) {
				throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
			}
			$recordId = (string) $record;
		} else {
			$recordModel = \App\Modules\Settings\AiPrompts\Models\Record::getCleanInstance();
		}

		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->assign('ACTION_OPTIONS', ActionRegistry::optionsForSelect());
		$viewer->assign(
			'PLACEHOLDERS_JSON',
			json_encode(
				array_column(ActionRegistry::optionsForSelect(), 'placeholders', 'key'),
				JSON_UNESCAPED_UNICODE
			)
		);
		$viewer->view('Edit.tpl', $moduleName);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.$moduleName.resources.Edit",
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);

		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
