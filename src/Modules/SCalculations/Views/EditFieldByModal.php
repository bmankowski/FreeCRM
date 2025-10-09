<?php

namespace FreeCRM\Modules\SCalculations\Views;

/**
 * EditFieldByModal View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class EditFieldByModal extends View
{

	protected $restrictItems = ['PLL_CANCELLED' => 'btn-danger', 'PLL_COMPLETED' => 'btn-success'];

	public function getConditionToRestricts($moduleName, $ID)
	{
		return Users_Privileges_Model::isPermitted($moduleName, 'CloseRecord', $ID);
	}
}
