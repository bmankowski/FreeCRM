<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\Settings\TemplateDynamicElements\Actions;

/**
 * Delete action for PDF dynamic elements.
 */
class DeleteAjax extends \App\Modules\Settings\Base\Actions\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordModel = \App\Modules\Settings\TemplateDynamicElements\Models\Record::getInstanceById($request->get('record'));
		$response = new \App\Http\Vtiger_Response();

		if (\App\Modules\Settings\TemplateDynamicElements\Models\Record::isCodeUsed((string) $recordModel->get('code'))) {
			$response->setResult([
				'success' => false,
				'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_DYNAMIC_ELEMENT_USED', $request->getModule(false)),
			]);
			$response->emit();
			return;
		}

		$response->setResult(['success' => $recordModel->delete()]);
		$response->emit();
	}
}
