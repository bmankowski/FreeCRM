<?php

namespace App\Modules\Settings\AdvancedPermission\Models;



/**
 * ListView Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

class ListView extends \\App\Modules\Settings\Vtiger\Models\ListView
{
	/*
	 * Function to get Basic links
	 * @return array of Basic links
	 */

	public function getBasicLinks()
	{
		$basicLinks = [];
		$moduleModel = $this->getModule();
		if ($moduleModel->hasCreatePermissions()) {
			$basicLinks[] = [
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_ADD_RECORD',
				'linkurl' => $moduleModel->getCreateRecordUrl(),
				'linkicon' => ''
			];
		}
		$basicLinks[] = [
			'linktype' => 'LISTVIEWBASIC',
			'linklabel' => 'LBL_RECALCULATE_PERMISSION_BTN',
			'linkurl' => 'javascript:app.showModalWindow(null, \'index.php?module=AdvancedPermission&parent=Settings&view=RecalculatePermission\')',
			'linkicon' => 'glyphicon glyphicon-cog',
		];

		return $basicLinks;
	}
}
