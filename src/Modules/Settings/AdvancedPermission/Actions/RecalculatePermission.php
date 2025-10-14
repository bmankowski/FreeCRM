<?php

namespace FreeCRM\Modules\Settings\AdvancedPermission\Actions;



/**
 * Save module to recalculate permissions
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class RecalculatePermission extends \FreeCRM\Modules\Settings\Vtiger\Actions\Save
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		\App\PrivilegeUpdater::setUpdater($request->get('moduleName'));
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}
}
