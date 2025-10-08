<?php

/**
 * EditFieldByModal View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class SRecurringOrders_EditFieldByModal_View extends Vtiger_EditFieldByModal_View
{

	protected $restrictItems = ['PLL_UNREALIZED' => 'btn-danger', 'PLL_REALIZED' => 'btn-success'];

	public function getConditionToRestricts($moduleName, $ID)
	{
		return Users_Privileges_Model::isPermitted($moduleName, 'CloseRecord', $ID);
	}
}
