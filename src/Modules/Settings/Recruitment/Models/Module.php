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

namespace App\Modules\Settings\Recruitment\Models;

class Module extends \App\Modules\Base\Models\Record
{
	public static function getCleanInstance(): self
	{
		return new self();
	}
}
