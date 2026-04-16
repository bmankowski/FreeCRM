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

final class AttachmentsTask extends AbstractCronTask
{
	public function execute(): void
	{
		\App\Modules\Base\Models\Files::getRidOfTrash(false, \App\Core\AppConfig::performance('CRON_MAX_ATACHMENTS_DELETE'));
	}
}
