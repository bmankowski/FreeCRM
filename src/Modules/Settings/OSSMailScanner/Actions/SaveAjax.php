<?php

namespace App\Modules\Settings\OSSMailScanner\Actions;



/**
 * Mail scanner action creating mail
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class SaveAjax extends \App\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('updateFolders');
	}

	public function updateFolders(\App\Http\Vtiger_Request $request)
	{
		$user = $request->get('user');
		$folders = $request->get('folders');
		$mailScannerRecordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance('OSSMailScanner');
		$mailScannerRecordModel->setFolderList($user, $folders);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_FOLDER_INFO', $request->getModule())
		]);
		$response->emit();
	}
}
