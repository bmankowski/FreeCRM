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

namespace App\Modules\Mail\Controllers;

/**
 * Mail is record-context + ACL gated, not a standalone profile-tab module.
 */
trait SkipsModuleProfilePermission
{
	public function requiresModulePermission(): bool
	{
		return false;
	}
}
