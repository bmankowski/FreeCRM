<?php

namespace App\Modules\Candidates\Actions;

use App\Exceptions\IllegalValue;

/**
 * Sen mail manually action model class.
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Adrian Koń <a.kon@yetiforce.com>
 */
class ToggleStarCandidateManuallyAjax extends \App\Controller\Action
{
	/**
	 * Process.
	 *
	 * @param \App\Request $request
	 */

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{

	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		try {
			$candidateId = $request->getInteger('candidateId');
		} catch (IllegalValue $e) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult([
				'success' => false,
				'message' => "PLL_TOGGLE_FAILED"
			]);
			$response->emit();
			return;
		}
		$candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId);
		$candidate->set("starred", $candidate->get("starred") == 1 ? 0 : 1);
		$candidate->save();
		$response = new Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => "PLL_TOGGLE_SUCCESS"
		]);
		$response->emit();
	}
}
