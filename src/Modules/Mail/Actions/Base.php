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

namespace App\Modules\Mail\Actions;

abstract class Base extends \App\Base\Controllers\BaseActionController
{
	use \App\Modules\Mail\Controllers\SkipsModuleProfilePermission;
}
