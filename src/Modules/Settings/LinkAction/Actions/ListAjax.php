<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Settings\LinkAction\Actions;

class ListAjax extends \App\Modules\Settings\Base\Actions\ListAjax
{
	public function getListViewCount(\App\Http\Vtiger_Request $request)
	{
		$listViewModel = \App\Modules\Settings\Base\Models\ListView::getInstance($request->getModule(false));

		$searchField = $request->get('search_key');
		$value = $request->get('search_value');
		if (!empty($searchField) && !empty($value)) {
			$listViewModel->set('search_key', $searchField);
			$listViewModel->set('search_value', $value);
		}

		return $listViewModel->getListViewCount();
	}
}
