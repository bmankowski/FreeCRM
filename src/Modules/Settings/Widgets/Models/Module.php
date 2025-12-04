<?php

namespace App\Modules\Settings\Widgets\Models;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Module extends \App\Modules\Settings\Base\Models\Module
{

	public static function getWidgets($module = false)
	{
		if ($module && !is_numeric($module)) {
			$module = \App\Utils\ModuleUtils::getModuleId($module);
		}
		if (\App\Cache\Cache::has('ModuleWidgets', $module)) {
			return \App\Cache\Cache::get('ModuleWidgets', $module);
		}
		$query = (new \App\Db\Query())->from('vtiger_widgets');
		if ($module) {
			$query->where(['tabid' => $module]);
		}
		$dataReader = $query->orderBy(['tabid' => SORT_ASC, 'sequence' => SORT_ASC])
				->createCommand()->query();
		$widgets = [1 => [], 2 => [], 3 => []];
		while ($row = $dataReader->read()) {
			$row['data'] = \App\Utils\Json::decode($row['data']);
			$widgets[$row['wcol']][$row['id']] = $row;
		}
		\App\Cache\Cache::save('ModuleWidgets', $module, $widgets);
		return $widgets;
	}

	public function getModulesList()
	{
		$modules = \vtlib\Functions:: getAllModules();
		foreach ($modules as $id => $module) {
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($module['name']);
			if (!$moduleModel->isSummaryViewSupported()) {
				unset($modules[$id]);
			}
		}
		return $modules;
	}

	public function getSize()
	{
		return [1, 2, 3];
	}

	public function getType($module = false)
	{
		// $module can be either tabid (numeric) or module name (string)
		if (is_numeric($module)) {
			$moduleName = \App\Utils\ModuleUtils::getModuleName($module);
		} else {
			$moduleName = $module;
		}
		
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$folderFiles = [];
		
		// Scan Base widgets directory
		$dir = 'src/Modules/Base/Widgets/';
		if (is_dir($dir)) {
			$ffs = scandir($dir);
			foreach ($ffs as $ff) {
				$action = str_replace('.php', "", $ff);
				if ($ff != '.' && $ff != '..' && !is_dir($dir . '/' . $ff) && $action != 'Basic') {
					$modelClassName = \App\Core\Loader::getComponentClassName('Widget', $action, 'Base', false);
					if ($modelClassName && class_exists($modelClassName)) {
						$instance = new $modelClassName();
						// Check if widget is allowed for this module
						$isAllowed = empty($instance->allowedModules) || in_array($moduleName, $instance->allowedModules);
						$commentsEnabled = ($action !== 'Comments') || !$moduleModel || $moduleModel->isCommentEnabled();
						
						if ($isAllowed && $commentsEnabled) {
							$folderFiles[$action] = $action;
						}
					}
				}
			}
		}
		
		// Also scan module-specific widgets (e.g., ProjektyRekrutacyjne/Widgets)
		if ($moduleName) {
			$moduleDir = 'src/Modules/' . $moduleName . '/Widgets/';
			if (is_dir($moduleDir)) {
				$moduleFiles = scandir($moduleDir);
				foreach ($moduleFiles as $ff) {
					$action = str_replace('.php', "", $ff);
					if ($ff != '.' && $ff != '..' && !is_dir($moduleDir . '/' . $ff) && $action != 'Basic') {
						$folderFiles[$action] = $action;
					}
				}
			}
		}
		
		return $folderFiles;
	}

	public function getColumns()
	{
		return [1, 2, 3, 4, 5, 6];
	}

	public function getRelatedModule($tabid)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$sql = 'SELECT vtiger_relatedlists.*,vtiger_tab.name FROM vtiger_relatedlists
				LEFT JOIN vtiger_tab ON vtiger_tab.tabid=vtiger_relatedlists.related_tabid WHERE vtiger_relatedlists.tabid = ? AND vtiger_relatedlists.related_tabid != 0';
		$result = $adb->pquery($sql, array($tabid));
		$relation = array();
		while ($row = $adb->fetch_array($result)) {
			$relation[$row['relation_id']] = $row;
		}
		return $relation;
	}

	public function getFiletrs($modules)
	{
		$filetrs = [];
		$tabid = [];
		foreach ($modules as $key => $value) {
			if (!in_array($value['related_tabid'], $tabid)) {
				$dataReader = (new \App\Db\Query())->select('columnname,tablename,fieldlabel,fieldname')
						->from('vtiger_field')
						->where(['tabid' => $value['related_tabid'], 'uitype' => [15, 16]])
						->createCommand()->query();
				while ($row = $dataReader->read()) {
					$filetrs[$value['related_tabid']][$row['fieldname']] = \App\Runtime\Vtiger_Language_Handler::translate($row['fieldlabel'], $value['name']);
				}
				$tabid[] = $value['related_tabid'];
			}
		}
		return $filetrs;
	}

	public function getCheckboxs($modules)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$checkboxs = [];
		$tabid = [];
		foreach ($modules as $key => $value) {
			if (!in_array($value['related_tabid'], $tabid)) {
				$dataReader = (new \App\Db\Query())->select('columnname,tablename,fieldlabel,fieldname')
						->from('vtiger_field')
						->where(['tabid' => $value['related_tabid'], 'uitype' => [56]])
						->andWhere(['<>', 'columnname', 'was_read'])
						->createCommand()->query();
				while ($row = $dataReader->read()) {
					$checkboxs[$value['related_tabid']][$row['tablename'] . '.' . $row['fieldname']] = \App\Runtime\Vtiger_Language_Handler::translate($row['fieldlabel'], $value['name']);
				}
				$tabid[] = $value['related_tabid'];
			}
		}
		return $checkboxs;
	}

	public function getFields($tabid, $uitype = false)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$fieldlabel = $fieldsList = [];
		$params = [$tabid];
		$sql = "SELECT fieldid,columnname,tablename,fieldlabel,fieldname FROM vtiger_field WHERE tabid = ? AND displaytype <> '2' AND vtiger_field.presence in (0,2)";
		if ($uitype) {
			$uitype = implode("','", $uitype);
			$sql .= " AND uitype in ('$uitype')";
		}
		$result = $adb->pquery($sql, $params, true);
		$Num = $adb->num_rows($result);
		while ($row = $adb->fetch_array($result)) {
			$fieldlabel[$row['fieldid']] = \App\Runtime\Vtiger_Language_Handler::translate($row['fieldlabel'], $value['name']);
			$fieldsList[$value['related_tabid']][$row['tablename'] . '::' . $row['columnname'] . '::' . $row['fieldname']] = \App\Runtime\Vtiger_Language_Handler::translate($row['fieldlabel'], $value['name']);
		}
		return array('labels' => $fieldlabel, 'table' => $fieldsList);
	}

	public static function saveWidget($params)
	{
		$db = \App\Db\Db::getInstance();
		$tabid = $params['tabid'];
		$data = $params['data'];
		$wid = $data['wid'];
		$type = $data['type'];
		
		// Initialize dbParams as empty array
		$dbParams = [];
		
		// Get source module name from tabid
		$sourceModuleName = \App\Utils\ModuleUtils::getModuleName($tabid);
		
		// Try to find widget class using Loader - first in source module, then in Base
		$widgetClassName = null;
		if ($sourceModuleName) {
			$widgetClassName = \App\Core\Loader::getComponentClassName('Widget', $type, $sourceModuleName, false);
		}
		if (!$widgetClassName) {
			$widgetClassName = \App\Core\Loader::getComponentClassName('Widget', $type, 'Base', false);
		}
		
		if ($widgetClassName && class_exists($widgetClassName)) {
			$widgetInstance = new $widgetClassName();
			if (isset($widgetInstance->dbParams) && is_array($widgetInstance->dbParams)) {
				$dbParams = $widgetInstance->dbParams;
			}
		}
		
		$data = array_merge($dbParams, $data);
		$label = $data['label'];
		unset($data['label']);
		$type = $data['type'];
		unset($data['type']);
		if (isset($data['FastEdit'])) {
			$FastEdit = array();
			if (!is_array($data['FastEdit'])) {
				$FastEdit[] = $data['FastEdit'];
				$data['FastEdit'] = $FastEdit;
			}
		}
		unset($data['filter_selected']);
		unset($data['wid']);
		$nomargin = isset($data['nomargin']) ? $data['nomargin'] : 0;
		unset($data['nomargin']);
		$serializeData = \App\Utils\Json::encode($data);
		$sequence = self::getLastSequence($tabid) + 1;
		if ($wid) {
			$db->createCommand()->update('vtiger_widgets', [
				'label' => $label,
				'nomargin' => $nomargin,
				'data' => $serializeData,
				], ['id' => $wid])->execute();
		} else {
			$db->createCommand()->insert('vtiger_widgets', [
				'tabid' => $tabid,
				'type' => $type,
				'label' => $label,
				'nomargin' => $nomargin,
				'sequence' => $sequence,
				'data' => $serializeData
			])->execute();
		}
	}

	public static function removeWidget($wid)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$adb->pquery('DELETE FROM vtiger_widgets WHERE id = ?;', array($wid));
	}

	public function getWidgetInfo($wid)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$sql = 'SELECT * FROM vtiger_widgets WHERE id = ?';
		$result = $adb->pquery($sql, array($wid));
		$resultrow = $adb->raw_query_result_rowdata($result);
		$resultrow['data'] = \App\Utils\Json::decode($resultrow['data']);
		return $resultrow;
	}

	public static function getLastSequence($tabid)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$sql = 'SELECT MAX(sequence) as max FROM vtiger_widgets WHERE tabid = ?';
		$result = $adb->pquery($sql, array($tabid));
		return $adb->query_result($result, 0, 'max');
	}

	public static function updateSequence($params)
	{
		$db = \App\Db\Db::getInstance();
		$tabid = $params['tabid'];
		$data = $params['data'];
		foreach ($data as $key => $value) {
			$db->createCommand()
				->update('vtiger_widgets', ['sequence' => $value['index'], 'wcol' => $value['column']], ['tabid' => $tabid, 'id' => $key])
				->execute();
		}
	}

	public function getWYSIWYGFields($tabid, $module)
	{
		$field = [];
		$adb = \App\Database\PearDatabase::getInstance();
		$sql = "SELECT fieldlabel,fieldname FROM vtiger_field WHERE tabid = ? AND uitype = ?;";
		$result = $adb->pquery($sql, [$tabid, '300']);
		while ($row = $adb->fetch_array($result)) {
			$field[$row['fieldname']] = \App\Runtime\Vtiger_Language_Handler::translate($row['fieldlabel'], $module);
		}
		return $field;
	}

	public static function getHeaderSwitch($index = [])
	{
		$data = [
			\App\Utils\ModuleUtils::getModuleId('SSalesProcesses') => [ 0 =>
				[
					'type' => 1,
					'label' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_HEADERSWITCH_OPEN_CLOSED', 'SSalesProcesses'), // used only in configuration
					'value' => ['ssalesprocesses_status' => ['PLL_SALE_COMPLETED', 'PLL_SALE_FAILED', 'PLL_SALE_CANCELLED']]
				]
			]
		];
		if (empty($index)) {
			return $data;
		} elseif ($data[$index[0]]) {
			return $data[$index[0]][$index[1]];
		} else {
			return [];
		}
	}

	/**
	 * Function to get buttons which visible in header widget 
	 * @param integer $moduleId Number id module
	 * @return \App\Modules\Base\Models\Link[]
	 */
	public static function getHeaderButtons($moduleId)
	{
		$linkList = [];
		$moduleName = \App\Utils\ModuleUtils::getModuleName($moduleId);
		if ($moduleName === 'Documents') {
			$linkList[] = [
				'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_MASS_ADD', $moduleName),
				'linkurl' => 'javascript:\Vtiger_Index_Js.massAddDocuments("index.php?module=Documents&view=MassAddDocuments")',
				'linkicon' => 'glyphicon glyphicon-plus',
				'linkclass' => 'btn-sm btn-primary'
			];
		}
		$buttons = [];
		foreach ($linkList as &$link) {
			$buttons[] = \App\Modules\Base\Models\Link::getInstanceFromValues($link);
		}
		return $buttons;
	}
}
