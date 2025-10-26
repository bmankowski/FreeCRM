<?php

namespace App\Modules\OutsourcedProducts\Models;

/**
 * OutsourcedProducts TreeView Model Class
 * @package YetiForce.TreeView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class TreeView extends \App\Modules\Base\Models\TreeView
{

	public function isActive()
	{
		return true;
	}
}
