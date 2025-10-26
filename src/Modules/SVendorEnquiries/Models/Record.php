<?php

namespace App\Modules\SVendorEnquiries\Models;

/**
 * Record Class for SVendorEnquiries
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Record extends \App\Modules\Base\Models\Record
{

	protected $privileges = ['editFieldByModal' => true];

	public function getFieldToEditByModal()
	{
		return [
			'addClass' => 'btn-default',
			'iconClass' => 'glyphicon-modal-window',
			'listViewClass' => '',
			'titleTag' => 'LBL_SET_RECORD_STATUS',
			'name' => 'svendorenquiries_status',
		];
	}
}
