<?php

namespace FreeCRM\Modules\Settings\OSSMailScanner\Actions;



/**
 * Mail scanner action creating mail
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('updateFolders');
	}

	public function updateFolders(\FreeCRM\Http\Vtiger_Request $request)
	{
		$user = $request->get('user');
		$folders = $request->get('folders');
		$mailScannerRecordModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance('OSSMailScanner');
		$mailScannerRecordModel->setFolderList($user, $folders);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => vtranslate('LBL_SAVE_FOLDER_INFO', $request->getModule())
		]);
		$response->emit();
	}
}
