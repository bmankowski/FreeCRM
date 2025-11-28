<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * UI entry point for ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Views;

use App\Modules\ImportManager\Controllers\WizardController;

class Wizard extends Upload
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$batchId = (int) $request->get('batch_id');
		if ($batchId > 0) {
			$controller = new WizardController();
			$stepUrl = $controller->getStepUrlForBatch($batchId);
			if ($stepUrl) {
				header('Location: ' . $stepUrl);
				exit;
			}
		}

		parent::process($request);
	}
}

