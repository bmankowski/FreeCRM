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

namespace App\Modules\Settings\CurrencyUpdate\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class CurrencyUpdateTask extends AbstractCronTask
{
	public function execute(): void
	{
		$moduleModel = \App\Modules\Settings\CurrencyUpdate\Models\Module::getCleanInstance();
		$moduleModel->refreshBanks();

		$dateCur = date('Y-m-d');
		if (strcmp(date('Y-m-d'), $dateCur) === 0) {
			$dateCur = date('Y-m-d', strtotime('-1 day', strtotime($dateCur)));
		}
		$dateCur = \vtlib\Functions::getLastWorkingDay($dateCur);

		$moduleModel->fetchCurrencyRates($dateCur, true);
	}
}
