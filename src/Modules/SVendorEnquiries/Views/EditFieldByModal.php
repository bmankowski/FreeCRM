<?php

namespace App\Modules\SVendorEnquiries\Views;

/**
 * EditFieldByModal View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class EditFieldByModal  extends \App\Modules\Vtiger\Views\Index
{

	protected $restrictItems = ['PLL_CANCELLED' => 'btn-danger', 'PLL_COMPLETED' => 'btn-success'];

	public function getConditionToRestricts($moduleName, $ID)
	{
		return \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CloseRecord', $ID);
	}
}
