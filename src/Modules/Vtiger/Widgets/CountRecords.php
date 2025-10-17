<?php

namespace App\Modules\Vtiger\Widgets;

/**
 * Class for count records widget
 * @package YetiForce.Widget
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class CountRecords extends \App\Modules\Vtiger\Widgets\Basic
{

	public $allowedModules = ['Campaigns'];

	public function getUrl()
	{
		$url = 'module=' . $this->Module . '&view=Detail&record=' . $this->Record . '&mode=showCountRecords';
		if (isset($this->Data['relatedModules'])) {
			foreach ($this->Data['relatedModules'] as $module) {
				$url .= '&relatedModules[]=' . $module;
			}
		}
		return $url;
	}

	public function getWidget()
	{
		$this->Config['tpl'] = 'CountRecords.tpl';
		$this->Config['url'] = $this->getUrl();
		$this->Config['relatedModules'] = $this->Data['relatedModules'];
		$widget = $this->Config;
		return $widget;
	}

	public function getConfigTplName()
	{
		return 'CountRecordsConfig';
	}

	static public function getCountRecords($modules, $recordId)
	{
		$countRecords = [];
		$parentRecordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId);
		foreach ($modules as $relatedModuleName) {
			$relationListView = \App\Modules\Vtiger\Models\RelationListView::getInstance($parentRecordModel, $relatedModuleName);
			if (!\App\Module::isModuleActive($relatedModuleName) || !$relationListView->getRelationModel()) {
				continue;
			}
			$countRecords[$relatedModuleName] = (int) $relationListView->getRelatedEntriesCount();
		}
		return $countRecords;
	}
}
