<?php

namespace FreeCRM\Modules\SVendorEnquiries\Views;

/**
 * EditFieldByModal View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class EditFieldByModal extends \Vtiger_Index_View
{

	protected $restrictItems = ['PLL_CANCELLED' => 'btn-danger', 'PLL_COMPLETED' => 'btn-success'];

	public function getConditionToRestricts($moduleName, $ID)
	{
		return \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CloseRecord', $ID);
	}
}
