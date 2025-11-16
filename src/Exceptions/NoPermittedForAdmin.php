<?php
namespace App\Exceptions;

/**
 * No Permitted Exception class
 * @package YetiForce.Exception
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class NoPermittedForAdmin extends \Exception
{

	/**
	 * Constructor
	 * @param string $message
	 * @param int $code
	 * @param \Exception $previous
	 */
	public function __construct($message = '', $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
		\App\Http\Vtiger_Session::init();

		$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
		$dbLog = \App\Database\PearDatabase::getInstance('log');
		$userName = \App\Http\Vtiger_Session::get('full_user_name');

		$data = [
			'username' => empty($userName) ? '-' : $userName,
			'date' => date('Y-m-d H:i:s'),
			'ip' => \App\Utils\RequestUtil::getRemoteIP(),
			'module' => $request->getModule(),
			'url' => \App\Utils\RequestUtil::getBrowserInfo()->url,
			'agent' => $_SERVER['HTTP_USER_AGENT'],
			'request' => json_encode($_REQUEST),
			'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''
		];
		\App\Db\Db::getInstance()->createCommand()->insert('o_#__access_for_admin', $data)->execute();
	}
}
