<?php

namespace App\Modules\Users\Views;

/**
 * User administration lives under Settings; non-settings URLs redirect there.
 */
class ListView extends \App\Modules\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$params = [
			'module' => 'Users',
			'parent' => 'Settings',
			'view' => 'ListView',
		];
		foreach (
			[
				'page',
				'viewname',
				'orderby',
				'sortorder',
				'status',
				'search_key',
				'search_value',
				'operator',
				'search_params',
				'searchResult',
			] as $key
		) {
			if ($request->has($key)) {
				$params[$key] = $request->get($key);
			}
		}
		header('Location: index.php?' . http_build_query($params));
		exit;
	}
}
