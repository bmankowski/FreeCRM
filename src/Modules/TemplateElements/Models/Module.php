<?php

namespace App\Modules\TemplateElements\Models;

class Module extends \App\Modules\Base\Models\Module
{
	public $name = 'TemplateElements';
	public $baseTable = 'u_yf_templateelements';
	public $baseIndex = 'templateelementsid';

	public static function getTypeSelectGroups(): array
	{
		return [
			[
				'groupLabel' => '',
				'types' => [
					'PLL_VARIABLE_ALIAS' => 'PLL_VARIABLE_ALIAS',
					'PLL_DOCUMENT_LAYOUT' => 'PLL_DOCUMENT_LAYOUT',
				],
			],
		];
	}

	public static function getAllowedTypes(): array
	{
		$allowed = [];
		foreach (self::getTypeSelectGroups() as $group) {
			$allowed = array_merge($allowed, array_keys($group['types']));
		}
		return array_values(array_unique($allowed));
	}
}
