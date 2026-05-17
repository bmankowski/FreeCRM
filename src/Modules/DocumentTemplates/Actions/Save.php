<?php

namespace App\Modules\DocumentTemplates\Actions;

/**
 * Wizard save action for document template steps.
 */
class Save extends \App\Modules\Base\Actions\Config
{
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\DocumentTemplates\Models\Module::checkRequestPermission($request);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$step = $request->get('step');
		$moduleName = $request->get('module_name');

		if ($recordId) {
			$pdfModel = \App\Modules\DocumentTemplates\Models\DocumentTemplate::getInstanceById($recordId);
		} else {
			$pdfModel = \App\Modules\DocumentTemplates\Models\Record::getCleanInstance($moduleName);
		}

		$stepFields = \App\Modules\DocumentTemplates\Models\Module::getFieldsByStep($step);
		$checkboxFields = ['metatags_status', 'margin_chkbox', 'default', 'one_file'];
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
				$pdfModel->set('conditions', '[]');
			}
			$pdfModel->set($field, $value);
		}
		if ($request->has('conditions')) {
			$pdfModel->set('conditions', $request->get('conditions'));
		}
		\App\Modules\DocumentTemplates\Models\Record::transformAdvanceFilterToWorkFlowFilter($pdfModel);
		\App\Modules\DocumentTemplates\Models\Record::encodeConditionsForDb($pdfModel);
		\App\Modules\DocumentTemplates\Models\Record::saveWizardStep($pdfModel, $step);

		if ((int) $step === 2) {
			$layoutSourceId = (int) $request->get('document_layout_source');
			if ($layoutSourceId > 0) {
				\App\Modules\DocumentTemplates\Models\Record::applyDocumentLayoutFromDynamicId($pdfModel, $layoutSourceId);
			}
		}

		if (!$request->isAjax() && (int) $step === 6) {
			header('Location: index.php?module=DocumentTemplates&view=ListView');
			exit;
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['id' => $pdfModel->getId()]);
		$response->emit();
	}
}
