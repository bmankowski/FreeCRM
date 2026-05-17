<?php

namespace App\Modules\DocumentTemplates\Actions;

class DeleteAjax extends \App\Modules\Base\Actions\Delete
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordModel = \App\Modules\DocumentTemplates\Models\Record::getInstanceById(
			$request->get('record'),
			$request->getModule()
		);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => (bool) $recordModel->delete()]);
		$response->emit();
	}
}
