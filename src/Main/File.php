<?php
namespace App\Main;

/**
 * Basic class to handle files
 *
 * @package YetiForce.Files
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class File
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		if (\App\Core\AppConfig::main('forceSSL') && !\App\Utils\RequestUtil::getBrowserInfo()->https) {
			header("Location: https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", true, 301);
		}
		if (\App\Core\AppConfig::main('forceRedirect')) {
			$requestUrl = (\App\Utils\RequestUtil::getBrowserInfo()->https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			if (stripos($requestUrl, \App\Core\AppConfig::main('site_URL')) !== 0) {
				header('Location: ' . \App\Core\AppConfig::main('site_URL'), true, 301);
			}
		}
		\App\Http\Vtiger_Session::init();
		$this->getLogin();
		$moduleName = $request->getModule();
		$action = $request->get('action');
		if (!$moduleName || !$action) {
			throw new \App\Exceptions\NoPermitted('Method Not Allowed', 405);
		}
		$handlerClass = \App\Core\Loader::getComponentClassName('File', $action, $moduleName);
		$handler = new $handlerClass();
		if ($handler) {
			$method = $request->getRequestMethod();
			$permissionFunction = $method . 'CheckPermission';
			if (!$handler->$permissionFunction($request)) {
				throw new \App\Exceptions\NoPermitted('LBL_NOT_ACCESSIBLE', 403);
			}
			$handler->$method($request);
		}
	}

	/**
	 * Function to get the instance of the logged in User
	 * @return Users object
	 */
	public function getLogin()
	{
		$userid = \App\Http\Vtiger_Session::getEffectiveUserId();
		if ($userid) {
			$userModel = \App\Modules\Users\Models\Record::getInstanceById($userid, 'Users');

			// NEW: Attach to request if available
			$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
			if ($request instanceof \App\Http\Vtiger_Request) {
				$request->setUser($userModel);
			}

			// Legacy entity for backward compatibility
			$user = \App\Core\CRMEntity::getInstance('Users');
			$user->retrieveCurrentUserInfoFromFile($userid);
			return $user;
		}
		throw new \App\Exceptions\NoPermitted('Unauthorized', 401);
	}
}
