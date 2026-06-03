<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Mail\Views;

class Compose extends \App\Modules\Base\Views\Index
{
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		return array_merge(
			parent::getFooterScripts($request),
			$this->checkAndConvertJsScripts([
				'modules.Mail.resources.SenderPicker',
				'modules.Mail.resources.Compose',
			])
		);
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$userId = (int) $request->getUser()->getId();
		$sourceModule = $request->getByType('sourceModule', 2);
		$sourceRecord = $request->getInteger('sourceRecord');
		$to = $request->getByType('to', 'Text');

		if ($sourceModule && $sourceRecord && !\App\Security\Privilege::isPermitted($sourceModule, 'DetailView', $sourceRecord)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}

		$templateModule = $sourceModule ?: 'Kandydaci';
		$templates = \App\Email\Mail::getTempleteList($templateModule);
		foreach ($templates as &$tpl) {
			$detail = \App\Email\Mail::getTempleteDetail($tpl['id']);
			$tpl['default_sender_ref'] = $detail
				? \App\Modules\Mail\Models\Module::defaultSenderRefForTemplate($detail, $userId)
				: '';
		}
		unset($tpl);
		$accounts = \App\Modules\Mail\Models\Service::getUserAccounts($userId, true);
		$smtpList = \App\Email\Mail::getAll();

		$viewer = $this->getViewer($request);
		$viewer->assign('SOURCE_MODULE', $sourceModule);
		$viewer->assign('SOURCE_RECORD', $sourceRecord);
		$viewer->assign('TO', $to);
		$viewer->assign('TEMPLATES', $templates);
		$viewer->assign('MAIL_ACCOUNTS', $accounts);
		$viewer->assign('SMTP_LIST', $smtpList);
		$viewer->assign('CAN_SEND', \App\Modules\Mail\Models\Module::canUserSend($userId));
		$viewer->view('Compose.tpl', 'Mail');
	}
}
