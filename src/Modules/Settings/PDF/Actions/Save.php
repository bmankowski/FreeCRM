<?php

namespace App\Modules\Settings\PDF\Actions;



/**
 * Save Action Class for PDF Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Save extends \App\Modules\Settings\Base\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$step = $request->get('step');
		$moduleName = $request->get('module_name');

		if ($recordId) {
			$pdfModel = \App\Modules\Base\Models\PDF::getInstanceById($recordId, $moduleName);
		} else {
			$pdfModel = \App\Modules\Settings\PDF\Models\Record::getCleanInstance($moduleName);
		}

		$stepFields = \App\Modules\Settings\PDF\Models\Module::getFieldsByStep($step);
		foreach ($stepFields as $field) {
			if ($field == 'body_content') {
				$value = $request->getForHtml($field);
			} else {
				$value = $request->get($field);
			}

			if (is_array($value)) {
				$value = implode(',', $value);
			}

			if ($field === 'module_name' && $pdfModel->get('module_name') != $value) {
				// change of main module, overwrite existing conditions
				$pdfModel->deleteConditions();
			}
			$pdfModel->set($field, $value);
		}
		$pdfModel->set('conditions', $request->get('conditions'));
		\App\Modules\Settings\PDF\Models\Record::transformAdvanceFilterToWorkFlowFilter($pdfModel);
		\App\Modules\Settings\PDF\Models\Record::save($pdfModel, $step);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['id' => $pdfModel->get('pdfid')]);
		$response->emit();
	}
}
