<?php

namespace App\Modules\Base\Actions;

/**
 * Fields Action Class
 * @package YetiForce.Actions
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Fields extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getOwners');
		$this->exposeMethod('searchReference');
		$this->exposeMethod('searchValues');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function getOwners(\App\Http\Vtiger_Request $request)
	{
		$searchValue = $request->get('value');
		$type = $request->get('type');
		if ($request->has('result')) {
			$result = $request->get('result');
		} else {
			$result = ['users', 'groups'];
		}

		$moduleName = $request->getModule();
		$response = new \App\Http\Vtiger_Response();
		if (empty($searchValue)) {
			$response->setError('NO');
		} else {
			$owner = \App\Fields\Owner::getInstance($moduleName);
			$owner->find($searchValue);

			$data = [];
			if (in_array('users', $result)) {
				$users = $owner->getAccessibleUsers('', 'owner');
				if (!empty($users)) {
					$data[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_USERS'), 'type' => 'optgroup'];
					foreach ($users as $key => &$value) {
						$data[] = ['id' => $key, 'name' => $value];
					}
				}
			}
			if (in_array('groups', $result)) {
				$grup = $owner->getAccessibleGroups('', 'owner', true);
				if (!empty($grup)) {
					$data[] = ['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_GROUPS'), 'type' => 'optgroup'];
					foreach ($grup as $key => &$value) {
						$data[] = ['id' => $key, 'name' => $value];
					}
				}
			}
			$response->setResult(['items' => $data]);
		}
		$response->emit();
	}

	/**
	 * Function searches for value data 
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function searchValues(\App\Http\Vtiger_Request $request)
	{
		$searchValue = $request->get('value');
		$fieldId = (int) $request->get('fld');
		$moduleName = $request->getModule();
		$response = new \App\Http\Vtiger_Response();
		if (empty($searchValue)) {
			$response->setError('NO');
		} else {
			if (\App\Fields\Field::getFieldPermission($moduleName, $fieldId) || $moduleName === 'Users') {
				$fieldModel = \App\Modules\Base\Models\Field::getInstanceFromFieldId($fieldId);
				$rows = $fieldModel->getUITypeModel()->getSearchValues($searchValue);
				foreach ($rows as $key => $value) {
					$data[] = ['id' => $key, 'name' => $value];
				}
				$response->setResult(['items' => $data]);
			} else {
				$response->setError('NO');
			}
		}
		$response->emit();
	}

	public function searchReference(\App\Http\Vtiger_Request $request)
	{
		$fieldId = $request->get('fid');
		$searchValue = $request->get('value');

		$fieldModel = \App\Modules\Base\Models\Field::getInstanceFromFieldId($fieldId);
		$reference = $fieldModel->getReferenceList();
		$rows = (new \App\Records\RecordSearch($searchValue, $reference))->search();
		$data = $modules = $ids = [];
		foreach ($rows as &$row) {
			$ids[] = $row['crmid'];
			$modules[$row['setype']][] = $row['crmid'];
		}
		$labels = \App\Records\Record::getLabel($ids);
		foreach ($modules as $moduleName => &$rows) {
			$data[] = ['name' => \App\Runtime\Vtiger_Language_Handler::getTranslatedString($moduleName, $moduleName), 'type' => 'optgroup'];
			foreach ($rows as &$id) {
				$data[] = ['id' => $id, 'name' => $labels[$id]];
			}
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['items' => $data]);
		$response->emit();
	}
}
