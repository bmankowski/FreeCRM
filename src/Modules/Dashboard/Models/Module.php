<?php

namespace App\Modules\Dashboard\Models;

use App\Modules\Vtiger\Models\Module as VtigerModule;

/**
 * Dashboard Module Model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Module extends VtigerModule
{

	public function isUtilityActionEnabled()
	{
		return true;
	}
}
