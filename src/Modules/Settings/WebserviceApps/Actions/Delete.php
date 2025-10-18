<?php

namespace App\Modules\Settings\WebserviceApps\Actions;



/**
 * Delete Application
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Delete extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$db->delete('w_yf_servers', 'id = ?', [$request->get('id')]);
	}
}
