<?php

namespace App\Modules\Assets\Models;

/**
 * Assets TreeView Model Class
 * @package YetiForce.TreeView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class TreeView extends \App\Modules\Vtiger\Models\TreeView
{

	public function isActive()
	{
		return true;
	}
}
