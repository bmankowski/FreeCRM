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

namespace App\Security;

/**
 * Default organization-wide module sharing policy.
 */
class ModuleSharingDefault
{
	public const TABLE = 'vtiger_tab_sharing_default';

	/** @var list<string> Modules hidden from Settings → Sharing Access */
	public const LOCKED_MODULE_NAMES = ['Contacts', 'Events'];

	public static function isLockedModule(string $moduleName): bool
	{
		return in_array($moduleName, self::LOCKED_MODULE_NAMES, true);
	}
}
