<?php

namespace App\Modules\Reservations\Views;

/**
 * 
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class Detail  extends \App\Modules\Vtiger\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showRelatedRecords');
	}
}
