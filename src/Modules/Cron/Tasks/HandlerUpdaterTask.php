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

namespace App\Modules\Cron\Tasks;

final class HandlerUpdaterTask extends AbstractCronTask
{
	public function execute(): void
	{
		$updaterLimit = 10;
		$cronMaxTime = 60;
		$interval = 2;
		$endTime = time() + $cronMaxTime;
		$eventHandler = new \App\Events\EventHandler();
		$db = \App\Db\Db::getInstance('admin');

		do {
			try {
				$rows = (new \App\Db\Query())->from('s_#__handler_updater')->limit($updaterLimit)->all($db);
				foreach ($rows as &$row) {
					$recordModel = \App\Modules\Base\Models\Record::getInstanceById($row['crmid'], \App\Utils\ModuleUtils::getModuleName($row['tabid']));
					$eventHandler->setRecordModel($recordModel);
					$eventHandler->setModuleName($recordModel->getModuleName());
					$eventHandler->setParams($row['params']);
					$eventHandler->setUser($row['params']);
					if (!empty($row['handler_name'])) {
						$eventHandler->trigger($row['handler_name']);
					} elseif (!empty($row['class'])) {
						$handlerInstance = new $row['class']();
						$handlerInstance->process($eventHandler);
					}
					$db->createCommand()->delete('s_#__handler_updater', ['id' => $row['id']])->execute();
				}
			} catch (\Throwable $e) {
				\App\Log\Log::error($e->getMessage(), 'CRON');
			}
			sleep($interval);
		} while (time() < $endTime);
	}
}
