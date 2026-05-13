<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\Settings\TemplateDynamicElements\Models;

/**
 * Module model for PDF dynamic elements settings.
 */
class Module extends \App\Modules\Settings\Base\Models\Module
{
	public $baseTable = 'a_yf_pdf_dynamic_elements';
	public $baseIndex = 'dynamicid';
	public $name = 'TemplateDynamicElements';
	public $nameFields = ['label'];
	public $listFields = [
		'label' => 'LBL_LABEL',
		'code' => 'LBL_CODE',
		'type' => 'LBL_TYPE',
		'module_name' => 'LBL_MODULE',
		'language' => 'LBL_LANGUAGE',
		'status' => 'LBL_STATUS',
		'sequence' => 'LBL_SEQUENCE',
	];

	/**
	 * Returns the module default list URL.
	 */
	public static function getDefaultUrl(): string
	{
		return 'index.php?module=TemplateDynamicElements&parent=Settings&view=ListView';
	}

	/**
	 * Returns the create record URL.
	 */
	public static function getCreateRecordUrl(): string
	{
		return 'index.php?module=TemplateDynamicElements&parent=Settings&view=Edit';
	}

	/**
	 * Type options for the edit form (optional optgroups).
	 * Only document layout and variable/alias are offered in the UI.
	 *
	 * @return array<int, array{groupLabel: string, types: array<string, string>}>
	 */
	public static function getTypeSelectGroups(): array
	{
		return [
			[
				'groupLabel' => '',
				'types' => [
					'PLL_DOCUMENT_LAYOUT' => 'PLL_DOCUMENT_LAYOUT',
					'PLL_VARIABLE_ALIAS' => 'PLL_VARIABLE_ALIAS',
				],
			],
		];
	}

	/**
	 * Flat list of allowed type values (for validation).
	 *
	 * @return string[]
	 */
	public static function getAllowedTypes(): array
	{
		$allowed = [];
		foreach (self::getTypeSelectGroups() as $group) {
			$allowed = array_merge($allowed, array_keys($group['types']));
		}
		return array_values(array_unique($allowed));
	}

}
