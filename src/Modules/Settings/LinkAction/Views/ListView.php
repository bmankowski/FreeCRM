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

namespace App\Modules\Settings\LinkAction\Views;

class ListView extends \App\Modules\Settings\Base\Views\ListView
{
	public function getPageTitle(\App\Http\Vtiger_Request $request): string
	{
		return 'LBL_LINK_ACTION_LOG';
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_FILTER_OPTIONS', \App\Modules\Settings\LinkAction\Models\ListView::getModuleFilterOptions());
		$viewer->assign('SELECTED_MODULE', $request->get('search_value'));
		parent::preProcess($request, false);
	}

	protected function prepareListViewData(\App\Http\Vtiger_Request $request): void
	{
		if (empty($request->get('orderby'))) {
			$request->set('orderby', 'clicked_at');
			$request->set('sortorder', 'DESC');
		}
		if ($request->get('search_key') === null && $request->has('module_filter') && $request->get('module_filter') !== '') {
			$request->set('search_key', 'module');
			$request->set('search_value', $request->get('module_filter'));
		}
		parent::prepareListViewData($request);
	}
}
