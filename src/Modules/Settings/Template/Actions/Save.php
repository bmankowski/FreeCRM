<?php

namespace App\Modules\Settings\Template\Actions;



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
			$pdfModel = \App\Modules\Settings\Template\Models\Record::getCleanInstance($moduleName);
		}

		$stepFields = \App\Modules\Settings\Template\Models\Module::getFieldsByStep($step);
		$checkboxFields = ['metatags_status', 'margin_chkbox', 'default', 'one_pdf'];
		foreach ($stepFields as $field) {
			if (in_array($field, ['header_content', 'body_content', 'footer_content'])) {
				$value = $request->getForHtml($field);
			} elseif (in_array($field, $checkboxFields) && !$request->has($field)) {
				$value = 0;
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
		\App\Modules\Settings\Template\Models\Record::transformAdvanceFilterToWorkFlowFilter($pdfModel);
		\App\Modules\Settings\Template\Models\Record::save($pdfModel, $step);

		if ((int) $step === 2) {
			$layoutSourceId = (int) $request->get('document_layout_source');
			if ($layoutSourceId > 0) {
				\App\Modules\Settings\Template\Models\Record::applyDocumentLayoutFromDynamicId($pdfModel, $layoutSourceId);
			}
		}

		if (!$request->isAjax() && (int) $step === 6) {
			header('Location: index.php?module=Template&parent=Settings&page=1&view=ListView');
			exit;
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['id' => $pdfModel->get('pdfid')]);
		$response->emit();
	}
}
