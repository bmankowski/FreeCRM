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

final class MailScannerVerificationTask extends AbstractCronTask
{
	public function execute(): void
	{
		$recordModel = \App\Modules\Base\Models\Record::getCleanInstance('OSSMailScanner');
		$recordModel->verificationCron();
	}
}
