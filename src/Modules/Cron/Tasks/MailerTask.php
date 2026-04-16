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

final class MailerTask extends AbstractCronTask
{
	public function execute(): void
	{
		$db = \App\Db\Db::getInstance('admin');
		$dataReader = (new \App\Db\Query())->from('s_#__mail_queue')
			->where(['status' => 1])
			->orderBy(['priority' => SORT_DESC, 'date' => SORT_ASC])
			->limit(\App\Core\AppConfig::performance('CRON_MAX_NUMBERS_SENDING_MAILS'))
			->createCommand($db)->query();

		while ($rowQueue = $dataReader->read()) {
			$status = \App\Email\Mailer::sendByRowQueue($rowQueue);
			if ($status) {
				$db->createCommand()->delete('s_#__mail_queue', ['id' => $rowQueue['id']])->execute();
			} else {
				$db->createCommand()->update('s_#__mail_queue', ['status' => 2], ['id' => $rowQueue['id']])->execute();
			}
		}
	}
}
