<?php

namespace FreeCRM\Modules\Settings\WebserviceApps\Actions;



/**
 * Delete Application
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Delete extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$db = \FreeCRM\database\PearDatabase::getInstance();
		$db->delete('w_yf_servers', 'id = ?', [$request->get('id')]);
	}
}
