<?php

namespace FreeCRM\Modules\SSalesProcesses\Actions;

/**
 * RelationAjax Class for SSalesProcesses
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class RelationAjax extends Action
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getHierarchyCount');
	}

	public function getHierarchyCount($request)
	{
		$sourceModule = $request->getModule();
		$recordId = $request->get('record');
		$focus = \FreeCRM\CRMEntity::getInstance($sourceModule);
		$hierarchy = $focus->getHierarchy($recordId);
		$response = new Vtiger_Response();
		$response->setResult(count($hierarchy['entries']) - 1);
		$response->emit();
	}
}
