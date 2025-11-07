<?php
namespace App\Api\Portal\BaseModule;

/**
 * Get Privileges class
 * @package YetiForce.WebserviceAction
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Modules\Vtiger\Models\Action as Vtiger_Action_Model;
class Privileges extends \App\Api\Core\BaseAction
{

	/** @var string[] Allowed request methods */
	public $allowedMethod = ['GET'];

	/**
	 * Get method
	 * @return array
	 */
	public function get()
	{
		$moduleName = $this->controller->request->get('module');
	$userId = $this->session->get('user_id');
	$privileges = [];
	if (\App\Modules\Users\Models\Record::isExists($userId)) {
		$moduleId = \App\Module::getModuleId($moduleName);
		$actionPermissions = \App\Modules\Users\Models\Record::getPrivilegesFile($userId);
			if ($actionPermissions === null) {
				\App\Log::error("User privileges file not found for user: $userId");
				return ['standardActions' => $privileges, 'error' => 'Privileges file not found'];
			}
			$isAdmin = $actionPermissions['is_admin'];
			$permission = isset($actionPermissions['profile_action_permission'][$moduleId]) ? $actionPermissions['profile_action_permission'][$moduleId] : false;
			if ($permission || $isAdmin) {
				foreach (\App\Modules\Vtiger\Models\Action::$standardActions as $key => $value) {
					$privileges[$value] = $isAdmin ? true : isset($permission[$key]) && $permission[$key] === \App\Modules\Settings\Profiles\Models\Module::IS_PERMITTED_VALUE;
				}
			}
		}
		return ['standardActions' => $privileges];
	}
}
