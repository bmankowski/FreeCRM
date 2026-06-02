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

namespace App\Modules\Settings\Recruitment\Views;

class Index extends \App\Modules\Settings\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		(new Transitions())->process($request);
	}
}
