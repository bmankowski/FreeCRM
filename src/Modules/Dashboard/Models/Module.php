<?php

namespace App\Modules\Dashboard\Models;


/**
 * Dashboard Module Model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Module extends \App\Modules\Base\Models\Module
{

	public function getDefaultUrl(): string
	{
		return 'index.php?module=Home&view=Index';
	}

	public function isUtilityActionEnabled()
	{
		return true;
	}
}
