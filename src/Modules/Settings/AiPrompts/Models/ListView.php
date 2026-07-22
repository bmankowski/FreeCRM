<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Settings\AiPrompts\Models;

class ListView extends \App\Modules\Settings\Base\Models\ListView
{
	public function getBasicListQuery()
	{
		$module = $this->getModule();

		return (new \App\Db\Query())
			->from($module->getBaseTable())
			->where(['userid' => null])
			->orderBy(['action_key' => SORT_ASC, 'name' => SORT_ASC]);
	}
}
