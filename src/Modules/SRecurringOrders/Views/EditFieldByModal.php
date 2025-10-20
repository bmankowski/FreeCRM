<?php

namespace App\Modules\SRecurringOrders\Views;

/**
 * EditFieldByModal View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class EditFieldByModal  extends \App\Modules\Vtiger\Views\Index
{

	protected $restrictItems = ['PLL_UNREALIZED' => 'btn-danger', 'PLL_REALIZED' => 'btn-success'];

	public function getConditionToRestricts($moduleName, $ID)
	{
		return \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CloseRecord', $ID);
	}
}
