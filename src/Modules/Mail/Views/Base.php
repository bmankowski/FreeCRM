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

namespace App\Modules\Mail\Views;

class Base extends \App\Modules\Base\Views\Index
{
	use \App\Modules\Mail\Controllers\SkipsModuleProfilePermission;
}
