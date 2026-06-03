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

namespace App\Modules\Settings\MailAccount\Models;

class Module extends \App\Modules\Settings\Base\Models\Module
{
	public static function getInstance(): self
	{
		return new self();
	}

	public function getCreateRecordUrl(): string
	{
		return 'index.php?module=MailAccount&parent=Settings&view=Edit';
	}
}
