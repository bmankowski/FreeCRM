<?php

namespace App\Modules\Base\Actions;

/**
 * Actions to pagination
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Pagination extends \App\Base\Controllers\BaseActionController
{

	public function __construct()
	{
		$this->exposeMethod('getTotalCount');
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}

	public function getTotalCount(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewName = $request->get('viewname');
		$listViewModel = \App\Modules\Base\Models\ListView::getInstance($moduleName, $viewName);
		$searchParmams = $request->get('search_params');
		if (!empty($searchParmams)) {
			// search_params can come as JSON string from AJAX
			if (is_string($searchParmams)) {
				$searchParmams = json_decode($searchParmams, true);
			}
		}
		if (empty($searchParmams) || !is_array($searchParmams)) {
			$searchParmams = [];
		}
		$searchParmams = $listViewModel->get('query_generator')->parseBaseSearchParamsToCondition($searchParmams);
		$listViewModel->set('search_params', $searchParmams);
		$totalCount = (int) $listViewModel->getListViewCount();
		$data = [
			'totalCount' => $totalCount
		];
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($data);
		$response->emit();
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}
}
