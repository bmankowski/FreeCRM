<?php

namespace App\Modules\Settings\AdvancedPermission\Actions;



/**
 * Save module to recalculate permissions
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class RecalculatePermission extends \App\Modules\Settings\Vtiger\Actions\Save
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		\App\PrivilegeUpdater::setUpdater($request->get('moduleName'));
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
