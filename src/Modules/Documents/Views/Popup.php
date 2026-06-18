<?php

namespace App\Modules\Documents\Views;

use App\Http\Vtiger_Request;

class Popup extends \App\Modules\Base\Views\Popup
{
	protected function applyDefaultOwnerFilter(Vtiger_Request $request): void
	{
		if ($request->has('search_params')) {
			return;
		}
		$userId = (int) ($request->getUser()->getId() ?? 0);
		if ($userId <= 0) {
			return;
		}
		$request->set('search_params', [[['assigned_user_id', 'e', (string) $userId]]]);
	}

	public function initializeListViewContents(Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$this->applyDefaultOwnerFilter($request);
		parent::initializeListViewContents($request, $viewer);
	}

	public function getListViewCount(Vtiger_Request $request)
	{
		$this->applyDefaultOwnerFilter($request);
		return parent::getListViewCount($request);
	}
}
