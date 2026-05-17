<?php

namespace App\Modules\TemplateElements\Actions;

class DeleteAjax extends \App\Modules\Base\Actions\Delete
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordModel = \App\Modules\TemplateElements\Models\Record::getInstanceById($request->get('record'), $request->getModule());
		$response = new \App\Http\Vtiger_Response();

		if (\App\Modules\TemplateElements\Models\Record::isCodeUsed((string) $recordModel->get('code'))) {
			$response->setResult([
				'success' => false,
				'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_DYNAMIC_ELEMENT_USED', $request->getModule()),
			]);
			$response->emit();
			return;
		}

		$response->setResult(['success' => (bool) $recordModel->delete()]);
		$response->emit();
	}
}
