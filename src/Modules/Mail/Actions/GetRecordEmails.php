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

namespace App\Modules\Mail\Actions;

class GetRecordEmails extends Base
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$moduleName = $request->get('sourceModule') ?: $request->get('mod');
		if (!$moduleName || !\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $request->get('sourceRecord') ?: $request->get('ids'))) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$recordId = (int) ($request->get('sourceRecord') ?: $request->get('ids'));
		$moduleName = (string) ($request->get('sourceModule') ?: $request->get('mod'));
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		$name = $recordModel->getName();
		$emailFields = [];
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		foreach ($moduleModel->getFieldsByType('email') as $field) {
			$email = (string) $recordModel->get($field->getName());
			if ($email !== '') {
				$emailFields[] = [
					'name' => $name,
					'fieldlabel' => \App\Runtime\Vtiger_Language_Handler::translate($field->get('label'), $moduleName),
					'email' => $email,
				];
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($emailFields);
		$response->emit();
	}
}
