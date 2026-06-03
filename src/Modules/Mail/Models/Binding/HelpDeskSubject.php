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

namespace App\Modules\Mail\Models\Binding;

class HelpDeskSubject
{
	public static function bind(int $messageId, string $subject): ?array
	{
		if (!preg_match('/\[T#(\d+)\]/', $subject, $m)) {
			return null;
		}
		$ticketNo = $m[1];
		$id = (new \App\Db\Query())
			->select('ticketid')
			->from('vtiger_troubletickets')
			->where(['ticket_no' => $ticketNo])
			->scalar();
		if (!$id) {
			return null;
		}
		if (Engine::link($messageId, 'HelpDesk', (int) $id, 'auto', 'ticket_no_in_subject')) {
			return ['module' => 'HelpDesk', 'id' => (int) $id];
		}
		return null;
	}
}
