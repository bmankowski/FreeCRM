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

class Module extends \App\Modules\Settings\Base\Models\Module
{
	public $baseTable = 's_#__ai_prompts';
	public $baseIndex = 'id';
	public $listFields = [
		'name' => 'LBL_NAME',
		'action_key' => 'LBL_ACTION_KEY',
		'active' => 'LBL_ACTIVE',
		'modifiedtime' => 'LBL_MODIFIEDTIME',
	];
	public $name = 'AiPrompts';

	public function getDefaultUrl(): string
	{
		return 'index.php?module=AiPrompts&parent=Settings&view=ListView';
	}

	public function getCreateRecordUrl(): string
	{
		return 'index.php?module=AiPrompts&parent=Settings&view=Edit';
	}
}
