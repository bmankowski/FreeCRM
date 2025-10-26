<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\Calendar\Actions;

class Invitees extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$userPrivilegesModel->hasModulePermission($moduleName)) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('find');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();

		if ($mode) {
			$this->invokeExposedMethod($mode, $request);
		}
	}

	public function find(\App\Http\Vtiger_Request $request)
	{
		$value = $request->get('value');
		$modules = array_keys(\App\ModuleHierarchy::getModulesByLevel(0));
		if (empty($modules)) {
			return [];
		}
		$rows = (new \App\RecordSearch($value, $modules, 10))->search();

		$matchingRecords = $leadIdsList = [];
		foreach ($rows as &$row) {
			if ($row['setype'] === 'Leads') {
				$leadIdsList[] = $row['crmid'];
			}
		}
		$convertedInfo = \App\Modules\Leads\Models\Module::getConvertedInfo($leadIdsList);
		foreach ($rows as &$row) {
			if ($row['setype'] === 'Leads' && $convertedInfo[$row['crmid']]) {
				continue;
			}
			if (\App\Modules\Users\Models\Privileges::isPermitted($row['moduleName'], 'DetailView', $row['crmid'])) {
				$label = \App\Record::getLabel($row['crmid']);
				$matchingRecords[] = [
					'id' => $row['crmid'],
					'module' => $row['setype'],
					'category' => \App\Runtime\Vtiger_Language_Handler::translate($row['setype'], $row['setype']),
					'fullLabel' => \App\Runtime\Vtiger_Language_Handler::translate($row['setype'], $row['setype']) . ': ' . $label,
					'label' => $label
				];
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($matchingRecords);
		$response->emit();
	}
}
