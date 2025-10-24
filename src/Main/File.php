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
		if (\App\AppConfig::main('forceSSL') && !\App\RequestUtil::getBrowserInfo()->https) {
			header("Location: https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", true, 301);
		}
		if (\App\AppConfig::main('forceRedirect')) {
			$requestUrl = (\App\RequestUtil::getBrowserInfo()->https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			if (stripos($requestUrl, \App\AppConfig::main('site_URL')) !== 0) {
				header('Location: ' . \App\AppConfig::main('site_URL'), true, 301);
			}
		}
		\App\Http\Vtiger_Session::init();
		$this->getLogin();
		$moduleName = $request->getModule();
		$action = $request->get('action');
		if (!$moduleName || !$action) {
			throw new \Exception\NoPermitted('Method Not Allowed', 405);
		}
		$handlerClass = \App\Loader::getComponentClassName('File', $action, $moduleName);
		$handler = new $handlerClass();
		if ($handler) {
			$method = $request->getRequestMethod();
			$permissionFunction = $method . 'CheckPermission';
			if (!$handler->$permissionFunction($request)) {
				throw new \Exception\NoPermitted('LBL_NOT_ACCESSIBLE', 403);
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
		if (\App\Http\Vtiger_Session::has('authenticated_user_id')) {
			$userid = \App\Http\Vtiger_Session::get('authenticated_user_id');
			if ($userid && \App\AppConfig::main('application_unique_key') === \App\Http\Vtiger_Session::get('app_unique_key')) {
				$userModel = \App\Modules\Users\Models\Record::getInstanceById($userid, 'Users');
				
				// NEW: Attach to request if available
				$request = \App\Http\AppRequest::init();
				if ($request instanceof \App\Http\Vtiger_Request) {
					$request->setUser($userModel);
				}
				
				// Legacy entity for backward compatibility
				$user = \App\CRMEntity::getInstance('Users');
				$user->retrieveCurrentUserInfoFromFile($userid);
				return $user;
			}
		}
		throw new \Exception\NoPermitted('Unauthorized', 401);
	}
}
