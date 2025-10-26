<?php

namespace App\Modules\SQuoteEnquiries\Views;

/**
 * EditFieldByModal View Class
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class EditFieldByModal  extends \App\Modules\Base\Views\Index
{

	protected $restrictItems = ['PLL_CANCELLED' => 'btn-danger', 'PLL_COMPLETED' => 'btn-success'];

	public function getConditionToRestricts($moduleName, $ID)
	{
		return \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CloseRecord', $ID);
	}
}
