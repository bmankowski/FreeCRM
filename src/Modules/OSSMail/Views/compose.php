<?php

namespace App\Modules\OSSMail\Views;

/**
 *
 * @package YetiForce.views
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class compose extends \Vtiger_Index_View
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$this->initAutologin();
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if (strpos($this->mainUrl, '?') !== false) {
			$this->mainUrl .= '&';
		} else {
			$this->mainUrl .= '?';
		}
		$this->mainUrl .= '_task=mail&_action=compose&_extwin=1';
		$params = \App\Modules\OSSMail\Models\Module::getComposeParam($request);
		$key = md5(count($params) . microtime());

		$db = \App\Database\PearDatabase::getInstance();
		$db->delete('u_yf_mail_compose_data', '`userid` = ?;', [$currentUser->getId()]);
		$db->insert('u_yf_mail_compose_data', [
			'key' => $key,
			'userid' => $currentUser->getId(),
			'data' => json_encode($params),
		]);
		$this->mainUrl .= '&_composeKey=' . $key;
		header('Location: ' . $this->mainUrl);
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		
	}
}
