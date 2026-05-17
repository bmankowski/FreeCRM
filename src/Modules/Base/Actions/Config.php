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

namespace App\Modules\Base\Actions;

/**
 * Base action for configuration-table modules.
 */
abstract class Config extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleClass = \App\Core\Loader::getComponentClassName('Model', 'Module', $moduleName);
		if (is_string($moduleClass) && method_exists($moduleClass, 'checkRequestPermission')) {
			$moduleClass::checkRequestPermission($request);
		}
	}
}
