<?php

namespace App\Modules\Settings\Vtiger\Actions;
use App\Modules\Settings\Vtiger\Models\Tracker;



/**
 * The basic class to save
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Save extends \App\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		parent::__construct();
	}
	
	public function process(\App\Http\Vtiger_Request $request)
	{
		// Initialize tracker with request parameter instead of AppRequest
		\App\Modules\Settings\Vtiger\Models\Tracker::setRecordId($request->get('record'));
		\App\Modules\Settings\Vtiger\Models\Tracker::addBasic('save');
		parent::process($request);
	}
}
