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

namespace App\Modules\Settings\AiPrompts\Actions;

class DeleteAjax extends \App\Modules\Settings\Base\Actions\Delete
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		$record = (int) $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = \App\Modules\Settings\AiPrompts\Models\Record::getInstanceById($record);
		if ($recordModel !== null) {
			$recordModel->delete();
		}

		$moduleModel = \App\Modules\Settings\Base\Models\Module::getInstance($qualifiedModuleName);
		header('Location: ' . $moduleModel->getDefaultUrl());
	}

	public function validateRequest(\App\Http\Vtiger_Request $request): void
	{
		$request->validateReadAccess();
	}
}
