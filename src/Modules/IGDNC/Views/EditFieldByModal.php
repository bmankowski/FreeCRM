<?php

namespace App\Modules\IGDNC\Views;

/**
 * EditFieldByModal View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class EditFieldByModal  extends \App\Modules\Vtiger\Views\Index
{

	protected $restrictItems = ['PLL_ACCEPTED' => 'btn-success'];

}
