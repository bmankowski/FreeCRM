<?php

namespace App\Modules\Settings\WebserviceApps\Actions;



/**
 * Save Application
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class SaveAjax extends \App\Modules\Settings\Base\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$keyLength = 32;
		$id = $request->get('id');
		$status = $request->get('status');
		$nameServer = $request->get('name');
		$url = $request->get('url');
		$pass = $request->get('pass');
		$accounts = $request->get('accounts');
		$db = \App\Db\Db::getInstance('webservice');
		if (empty($id)) {
			$type = $request->get('type');
			$key = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $keyLength);
			$db->createCommand()->insert('w_#__servers', [
				'name' => $nameServer,
				'acceptable_url' => $url,
				'api_key' => $key,
				'status' => $status == 'true' ? 1 : 0,
				'type' => $type,
				'pass' => $pass,
				'accounts_id' => $accounts,
			])->execute();
		} else {
			$updates = [
				'status' => $status == 'true' ? 1 : 0,
				'name' => $nameServer,
				'acceptable_url' => $url,
				'pass' => $pass,
				'accounts_id' => $accounts,
			];
			$db->createCommand()->update('w_#__servers', $updates, ['id' => $id])->execute();
		}
	}
}
