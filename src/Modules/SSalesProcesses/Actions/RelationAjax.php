<?php

namespace App\Modules\SSalesProcesses\Actions;

/**
 * RelationAjax Class for SSalesProcesses
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class RelationAjax extends \App\Base\Controllers\BaseActionController
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getHierarchyCount');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function getHierarchyCount($request)
	{
		$sourceModule = $request->getModule();
		$recordId = $request->get('record');
		$currentUser = $request->getUser();
		$focus = \App\Core\CRMEntity::getInstance($sourceModule);
		$hierarchy = $focus->getHierarchy($recordId, false, true, $currentUser);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(count($hierarchy['entries']) - 1);
		$response->emit();
	}
}
