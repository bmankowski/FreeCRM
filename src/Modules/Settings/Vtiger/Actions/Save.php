<?php

namespace FreeCRM\Modules\Settings\Vtiger\Actions;
use FreeCRM\Modules\Settings\Vtiger\Models\Tracker;



/**
 * The basic class to save
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Save extends \FreeCRM\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::setRecordId(\FreeCRM\Http\AppRequest::get('record'));
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::addBasic('save');
		parent::__construct();
	}
}
