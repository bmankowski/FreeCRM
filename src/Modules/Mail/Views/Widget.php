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

class Widget extends Base
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$srecord = (int) $request->get('srecord');
		$smodule = (string) $request->get('smodule');
		if (!\App\Modules\Users\Models\Privileges::isPermitted($smodule, 'DetailView', $srecord)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true): void
	{
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$smodule = (string) $request->get('smodule');
		$srecord = (int) $request->get('srecord');
		$limit = (int) ($request->get('limit') ?: 5);
		$direction = $request->get('type');
		$userId = (int) \App\User\CurrentUser::getId();
		$rows = \App\Modules\Mail\Models\Service::getMessagesForRecord($smodule, $srecord, $userId, $limit);
		if ($direction !== null && $direction !== '' && $direction !== 'All') {
			$map = ['0' => 'out', '1' => 'in', '2' => 'internal'];
			$dir = $map[(string) $direction] ?? null;
			if ($dir !== null) {
				$rows = array_values(array_filter($rows, static fn(array $r): bool => ($r['direction'] ?? '') === $dir));
			}
		}
		$list = [];
		foreach ($rows as $row) {
			$list[] = [
				'id' => (int) $row['id'],
				'subject' => $row['subject'] ?? '',
				'from_email' => $row['from_email'] ?? '',
				'direction' => $row['direction'] ?? 'in',
				'date' => $row['date_sent'] ?? '',
				'send_status' => $row['send_status'] ?? null,
				'opened_at_display' => $row['opened_at_display'] ?? '',
				'attachments_exist' => !empty($row['has_attachments']),
				'url' => \App\Modules\Mail\Models\Module::getMessageDetailUrl((int) $row['id'], $smodule, $srecord),
			];
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('MAIL_ROWS', $list);
		$viewer->assign('SMODULENAME', $smodule);
		$viewer->assign('SRECORD', $srecord);
		$viewer->assign('CAN_SEND', \App\Core\AppConfig::main('isActiveSendingMails')
			&& \App\Modules\Mail\Models\Module::canUserSend($userId));
		$viewer->assign('COMPOSE_URL', \App\Modules\Mail\Models\Module::getComposeUrl($smodule, $srecord));
		$viewer->view('widgets.tpl', 'Mail');
	}
}
