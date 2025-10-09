<?php

namespace FreeCRM\Modules\Accounts\Actions;

/**
 * RelationAjax Class for Accounts
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
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
		$hierarchy = $focus->getAccountHierarchy($recordId);
		$response = new Vtiger_Response();
		$response->setResult(count($hierarchy['entries']) - 1);
		$response->emit();
	}
}
