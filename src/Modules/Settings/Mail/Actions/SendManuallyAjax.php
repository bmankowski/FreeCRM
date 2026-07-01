<?php

namespace App\Modules\Settings\Mail\Actions;



/**
 * Sen mail manually action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class SendManuallyAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{

	/**
	 * Checking permission 
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermittedForAdmin
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdmin()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('id');
		$db = \App\Db\Db::getInstance('admin');
		$row = (new \App\Db\Query())->from('s_#__mail_queue')
				->where(['id' => $record])->one($db);
		$status = \App\Modules\Mail\Models\Outbound::deliverFromQueueRow($row, (int) ($row['owner'] ?? 0));
		if ($status) {
			$db->createCommand()->delete('s_#__mail_queue', ['id' => $row['id']])->execute();
		} else {
			$db->createCommand()->update('s_#__mail_queue', ['status' => 2], ['id' => $row['id']])->execute();
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEND_EMAIL_MANUALLY', $request->getModule(false))]);
		$response->emit();
	}

	/**
	 * Validate Request
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
