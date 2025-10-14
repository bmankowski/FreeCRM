<?php

namespace FreeCRM\Modules\Settings\Mail\Actions;



/**
 * Sen mail manually action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class SendManuallyAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	/**
	 * Checking permission 
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @throws \Exception\NoPermittedForAdmin
	 */
	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\User::getCurrentUserModel();
		if (!$currentUserModel->isAdmin()) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Process
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('id');
		$db = \App\Db::getInstance('admin');
		$row = (new \App\Db\Query())->from('s_#__mail_queue')
				->where(['id' => $record])->one($db);
		$status = \App\Mailer::sendByRowQueue($row);
		if ($status) {
			$db->createCommand()->delete('s_#__mail_queue', ['id' => $row['id']])->execute();
		} else {
			$db->createCommand()->update('s_#__mail_queue', ['status' => 2], ['id' => $row['id']])->execute();
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['success' => true, 'message' => \LanguageTranslator::translate('LBL_SEND_EMAIL_MANUALLY', $request->getModule(false))]);
		$response->emit();
	}

	/**
	 * Validate Request
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
