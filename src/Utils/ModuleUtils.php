<?php
namespace App\Utils;

use App\Cache\Cache;

/**
 * Module Utils - Utility class for module metadata operations
 * 
 * This is a static utility class providing helper methods for working with module metadata.
 * It does NOT represent a specific module instance - use App\Modules\Base\Models\Module::getInstance()
 * for that purpose.
 * 
 * All methods are static - no instantiation needed or possible.
 * 
 * Responsibilities:
 * - Module ID/Name conversions (getModuleId, getModuleName)
 * - Module entity info retrieval (getEntityInfo, getAllEntityModuleInfo)
 * - Module status checks (isModuleActive)
 * - Tab data access (getTabData)
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ModuleUtils
{

	protected static $moduleEntityCacheById = [];

	static public function getEntityInfo($mixed = false)
	{
		$entity = false;
		if ($mixed) {
			if (is_numeric($mixed)) {
				if (Cache::has('ModuleEntityById', $mixed)) {
					return Cache::get('ModuleEntityById', $mixed);
				}
			} else {
				if (Cache::has('ModuleEntityByName', $mixed)) {
					return Cache::get('ModuleEntityByName', $mixed);
				}
			}
		}
		// Load all entity data from database if not in cache
		$dataReader = (new \App\Db\Query())->from('vtiger_entityname')
				->createCommand()->query();
		while ($row = $dataReader->read()) {
			$row['fieldnameArr'] = explode(',', $row['fieldname']);
			$row['searchcolumnArr'] = explode(',', $row['searchcolumn']);
			Cache::save('ModuleEntityByName', $row['modulename'], $row);
			Cache::save('ModuleEntityById', $row['tabid'], $row);
			static::$moduleEntityCacheById[$row['tabid']] = $row;
		}
		if ($mixed) {
			if (is_numeric($mixed)) {
				$fromDb = Cache::get('ModuleEntityById', $mixed);
				if ($fromDb) {
					return $fromDb;
				}
			} else {
				$fromDb = Cache::get('ModuleEntityByName', $mixed);
				if ($fromDb) {
					return $fromDb;
				}
			}
			// Fallback: some custom modules may be missing `vtiger_entityname` row.
			// Synthesize minimal entity metadata from the module's CRMEntity definition.
			$moduleName = is_numeric($mixed) ? static::getModuleName((int) $mixed) : (string) $mixed;
			if (!$moduleName) {
				return false;
			}
			try {
				$focus = \App\Core\CRMEntity::getInstance($moduleName);
			} catch (\Throwable $e) {
				return false;
			}
			if (empty($focus) || empty($focus->table_name) || empty($focus->table_index)) {
				return false;
			}
			$tabId = static::getModuleId($moduleName);
			if (!$tabId) {
				return false;
			}
			$nameField = $focus->def_detailview_recname ?? $focus->def_basicsearch_col ?? null;
			if (empty($nameField)) {
				$nameField = 'name';
			}
			$row = [
				'tabid' => (int) $tabId,
				'modulename' => $moduleName,
				'tablename' => (string) $focus->table_name,
				'fieldname' => (string) $nameField,
				'entityidfield' => (string) $focus->table_index,
				'entityidcolumn' => (string) $focus->table_index,
				'searchcolumn' => (string) $nameField,
				'turn_off' => 1,
				'sequence' => 0,
			];
			$row['fieldnameArr'] = explode(',', $row['fieldname']);
			$row['searchcolumnArr'] = explode(',', $row['searchcolumn']);
			Cache::save('ModuleEntityByName', $row['modulename'], $row);
			Cache::save('ModuleEntityById', $row['tabid'], $row);
			static::$moduleEntityCacheById[$row['tabid']] = $row;
			return $row;
		}
		return $entity;
	}

	static public function getAllEntityModuleInfo($sort = false)
	{
		if (empty(static::$moduleEntityCacheById)) {
			static::getEntityInfo();
		}
		$entity = [];
		if ($sort) {
			foreach (static::$moduleEntityCacheById as $row) {
				$entity[$row['sequence']] = $row;
			}
			ksort($entity);
		} else {
			$entity = static::$moduleEntityCacheById;
		}
		return $entity;
	}

	protected static $isModuleActiveCache = [];

	static public function isModuleActive($moduleName)
	{
		if (isset(static::$isModuleActiveCache[$moduleName])) {
			return static::$isModuleActiveCache[$moduleName];
		}
		$moduleAlwaysActive = ['Administration', 'CustomView', 'Settings', 'Users', 'Migration',
			'Utilities', 'uploads', 'Import', 'System', 'com_vtiger_workflow', 'PickList'
		];
		if (in_array($moduleName, $moduleAlwaysActive)) {
			static::$isModuleActiveCache[$moduleName] = true;
			return true;
		}
		$tabPresence = static::getTabData('tabPresence');
		$moduleId = static::getModuleId($moduleName);
		$isActive = (isset($tabPresence[$moduleId]) && $tabPresence[$moduleId] == 0) ? true : false;
		static::$isModuleActiveCache[$moduleName] = $isActive;
		return $isActive;
	}

	protected static $tabdataCache = false;

	static public function getTabData($type)
	{
		if (static::$tabdataCache === false) {
			static::$tabdataCache = require 'user_privileges/tabdata.php';
		}
		return isset(static::$tabdataCache[$type]) ? static::$tabdataCache[$type] : false;
	}

	public static function getModuleId($name)
	{
		$tabId = static::getTabData('tabId');
		return isset($tabId[$name]) ? $tabId[$name] : false;
	}

	public static function getModuleName($tabId)
	{
		$tabIdMap = static::getTabData('tabId');
		if ($tabIdMap === false) {
			return false;
		}
		$tabNameMap = array_flip($tabIdMap);
		return isset($tabNameMap[$tabId]) ? $tabNameMap[$tabId] : false;
	}

	/**
	 * Function get module name
	 * @param string $moduleName
	 * @return string
	 */
	public static function getTabName($moduleName)
	{
		return $moduleName === 'Events' ? 'Calendar' : $moduleName;
	}

	/**
	 * Function to get the list of module for which the user defined sharing rules can be defined
	 * @param array $eliminateModules
	 * @return array
	 */
	public static function getSharingModuleList($eliminateModules = false)
	{
		$modules = \vtlib\Functions:: getAllModules(true, true, 0, false, 0);
		$sharingModules = [];
		foreach ($modules as $tabId => $row) {
			if (!$eliminateModules || !in_array($row['name'], $eliminateModules)) {
				$sharingModules[] = $row['name'];
			}
		}
		return $sharingModules;
	}

	/**
	 * Get sql for name in display format
	 * @param string $moduleName
	 * @return string
	 */
	public static function getSqlForNameInDisplayFormat($moduleName)
	{
		$entityFieldInfo = static::getEntityInfo($moduleName);
		$fieldsName = $entityFieldInfo['fieldnameArr'];
		if (count($fieldsName) > 1) {
			$sqlString = 'CONCAT(';
			foreach ($fieldsName as &$column) {
				$sqlString .= "{$entityFieldInfo['tablename']}.$column,' ',";
			}
			$formattedName = new \yii\db\Expression(rtrim($sqlString, ',\' \',') . ')');
		} else {
			$fieldsName = array_pop($fieldsName);
			$formattedName = "{$entityFieldInfo['tablename']}.$fieldsName";
		}
		return $formattedName;
	}
}

