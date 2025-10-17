<?php

namespace App\Modules\Settings\LoginHistory\Actions;



/**
 * 
 * @package YetiForce.Actions
 * @license licenses/License.html
 * @author Mriusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Modules\Vtiger\Models\ListView as Vtiger_ListView_Model;
class ListAjax extends \App\Modules\Settings\Vtiger\Actions\ListAjax
{

	public function getListViewCount(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);

		$listViewModel = Settings_Vtiger_ListView_Model::getInstance($qualifiedModuleName);

		$searchField = $request->get('search_key');
		$value = $request->get('search_value');

		if (!empty($searchField) && !empty($value)) {
			$listViewModel->set('search_key', $searchField);
			$listViewModel->set('search_value', $value);
		}

		return $listViewModel->getListViewCount();
	}
}
