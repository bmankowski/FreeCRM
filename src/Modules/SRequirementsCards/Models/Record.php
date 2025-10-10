<?php

namespace FreeCRM\Modules\SRequirementsCards\Models;

/**
 * Record Class for SRequirementsCards
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Record extends \FreeCRM\Modules\Vtiger\Models\Record
{

	protected $privileges = ['editFieldByModal' => true];

	public function getFieldToEditByModal()
	{
		return [
			'addClass' => 'btn-default',
			'iconClass' => 'glyphicon-modal-window',
			'listViewClass' => '',
			'titleTag' => 'LBL_SET_RECORD_STATUS',
			'name' => 'srequirementscards_status',
		];
	}
}
