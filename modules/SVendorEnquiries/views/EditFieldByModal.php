<?php

/**
 * EditFieldByModal View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class SVendorEnquiries_EditFieldByModal_View extends Vtiger_EditFieldByModal_View
{

	protected $restrictItems = ['PLL_CANCELLED' => 'btn-danger', 'PLL_COMPLETED' => 'btn-success'];

	public function getConditionToRestricts($moduleName, $ID)
	{
		return Users_Privileges_Model::isPermitted($moduleName, 'CloseRecord', $ID);
	}
}
