<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\TemplateElements\Views;

/**
 * Template elements use the Edit screen (no separate detail layout).
 */
class Detail extends \App\Modules\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!\App\Modules\Users\Models\Privileges::isPermitted($request->getModule(), 'DetailView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		$recordId = $request->get('record');
		if (!is_numeric($recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		$exists = (new \App\Db\Query())
			->from('u_yf_templateelements')
			->where(['templateelementsid' => (int) $recordId])
			->exists();
		if (!$exists) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_RECORD_NOT_FOUND');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$params = [
			'module' => $request->getModule(),
			'view' => 'Edit',
			'record' => (int) $request->get('record'),
		];
		if ($request->has('mid')) {
			$params['mid'] = $request->get('mid');
		}
		if ($request->has('parent')) {
			$params['parent'] = $request->get('parent');
		}
		header('Location: index.php?' . http_build_query($params));
		exit;
	}
}
