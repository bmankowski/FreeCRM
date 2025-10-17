<?php

namespace App\Modules\Settings\Vtiger\Actions;
use App\Modules\Settings\Vtiger\Models\Tracker;



/**
 * The basic class to delete
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Delete extends \App\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		\App\Modules\Settings\Vtiger\Models\Tracker::setRecordId(\App\Http\AppRequest::get('record'));
		\App\Modules\Settings\Vtiger\Models\Tracker::addBasic('delete');
		parent::__construct();
	}
}
