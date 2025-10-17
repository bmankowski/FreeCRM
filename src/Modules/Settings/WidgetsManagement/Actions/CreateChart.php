<?php

namespace App\Modules\Settings\WidgetsManagement\Actions;



/**
 * Action to create widget
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class CreateChart extends \App\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$db = \App\database\PearDatabase::getInstance();
		$linkId = $request->get('linkId');
		$chartName = $request->get('chartName');
		$blockid = $request->get('blockid');
		$isDefault = $request->get('isDefault');
		$width = $request->get('width');
		$height = $request->get('height');
		$size = \App\Json::encode(['width' => $width, 'height' => $height]);
		$data = \App\Json::encode(['reportId' => $request->get('reportId')]);
		$paramsToInsert = [
			'linkid' => $linkId,
			'blockid' => $blockid,
			'filterid' => 0,
			'title' => $chartName,
			'isdefault' => $isDefault,
			'size' => $size,
			'data' => $data
		];
		$db->insert('vtiger_module_dashboard', $paramsToInsert);
		$id = $db->getLastInsertID();
		$result = [];
		$result['success'] = true;
		$result['widgetId'] = $id;
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
