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

namespace App\Modules\OSSMailScanner\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class MailScannerActionTask extends AbstractCronTask
{
	public function execute(): void
	{
		$recordModel = \App\Modules\Base\Models\Record::getCleanInstance('OSSMailScanner');
		$user_name = '';
		if (PHP_SAPI === 'cgi-fcgi') {
			$user_name = \App\User\CurrentUser::get()->user_name;
		}
		$recordModel->executeCron(PHP_SAPI . ' - ' . $user_name);
	}
}
