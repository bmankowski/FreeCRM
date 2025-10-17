<?php

namespace App\Modules\Vtiger\Models;

/**
 * Basic MappedFields Model Class
 * @package YetiForce.MappedFields
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class MappedFields extends Model
{

	public static $baseTable = 'a_yf_mapped_config';
	public static $mappingTable = 'a_yf_mapped_fields';
	public static $baseIndex = 'id';
	public static $mappingIndex = 'mappedid';
	protected $mapping = [];

	/**
	 * Function to get the id of the record
	 * @return <Number> - Record Id
	 */
	public function getId()
	{
		return $this->get('id');
	}

	public function get($key)
	{
		if (in_array($key, ['conditions', 'params']) && !is_array(parent::get($key))) {
			return \App\Json::decode(html_entity_decode(parent::get($key)));
		} else {
			return parent::get($key);
		}
	}

	public function getRaw($key)
	{
		return parent::get($key);
	}

	public function getModule()
	{
		return \App\Modules\Vtiger\Models\Module::getInstance($this->getName());
	}

	public function getRelatedModule()
	{
		return \App\Modules\Vtiger\Models\Module::getInstance($this->getRelatedName());
	}

	/**
	 * Check if templates are avauble for this record, user and view
	 * @param integer $recordId - id of a record
	 * @param string $moduleName - name of the module
	 * @param string $view - modules view - Detail or List
	 * @return bool true or false
	 */
	public function checkActiveTemplates($recordId, $moduleName, $view)
	{
		$templates = $this->getActiveTemplatesForRecord($recordId, $view, $moduleName);

		if (count($templates) > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function getActiveTemplatesForRecord($recordId, $view, $moduleName = false)
	{

		\App\Log::trace('Entering ' . __METHOD__ . '(' . $recordId . ',' . $view . ',' . $moduleName . ') method ...');
		if (!isRecordExists($recordId)) {
			\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
			return [];
		}
		if (!$moduleName) {
			$moduleName = \vtlib\Functions::getCRMRecordType($recordId);
		}

		$templates = $this->getTemplatesByModule($moduleName);
		foreach ($templates as $id => &$template) {
			if (!$template->checkFiltersForRecord($recordId) || !$template->checkUserPermissions() || !\App\Modules\Users\Models\Privileges::isPermitted($template->getRelatedName(), 'EditView')) {
				unset($templates[$id]);
			}
		}
		\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $templates;
	}

	/**
	 * Returns template records by module name
	 * @param string $moduleName - module name for which template was created
	 * @return array of template record models
	 */
	public static function getTemplatesByModule($moduleName)
	{
		\App\Log::trace('Entering ' . __METHOD__ . '(' . $moduleName . ') method ...');
		if (\App\Cache::has('MappedFieldsTemplatesByModule', $moduleName)) {
			$rows = \App\Cache::get('MappedFieldsTemplatesByModule', $moduleName);
		} else {
			$rows = (new \App\Db\Query())->from(self::$baseTable)
				->where(['tabid' => \App\Module::getModuleId($moduleName), 'status' => 1])
				->all();
			\App\Cache::save('MappedFieldsTemplatesByModule', $moduleName, $rows);
		}
		$templates = [];
		foreach ($rows as $row) {
			$handlerClass = \App\Loader::getComponentClassName('Model', 'MappedFields', $moduleName);
			$mf = new $handlerClass();
			$mf->setData($row);
			$templates[$mf->getId()] = $mf;
		}
		\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $templates;
	}

	public function getActiveTemplatesForModule($moduleName, $view)
	{

		\App\Log::trace('Entering ' . __METHOD__ . '(' . $moduleName . ',' . $view . ') method ...');
		$templates = $this->getTemplatesByModule($moduleName);
		foreach ($templates as $id => &$template) {
			$active = true;
			if (!$template->checkUserPermissions()) {
				unset($templates[$id]);
			}
		}
		\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $templates;
	}

	public static function getInstanceByModules($tabId, $relTabId)
	{

		\App\Log::trace('Entering ' . __METHOD__ . '(' . $tabId . ',' . $relTabId . ') method ...');
		$row = (new \App\Db\Query())->from(self::$baseTable)->where(['tabid' => $tabId, 'reltabid' => $relTabId])->limit(1)->one();
		if ($row === false) {
			\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
			return false;
		}

		$handlerClass = \App\Loader::getComponentClassName('Model', 'MappedFields', \vtlib\Functions::getModuleName($tabId));
		$mf = new $handlerClass();
		$mf->setData($row);
		\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $mf;
	}

	public static function getInstanceById($recordId, $moduleName = 'Vtiger')
	{
		\App\Log::trace('Entering ' . __METHOD__ . '(' . $recordId . ',' . $moduleName . ') method ...');
		$mf = \App\Runtime\Vtiger_Cache::get('MappedFieldsModel', $recordId);
		if ($mf) {
			\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
			return $mf;
		}
		$row = (new \App\Db\Query())->from(self::$baseTable)
			->where([self::$baseIndex => $recordId])
			->one();
		if ($row === false) {
			\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
			return false;
		}
		$handlerClass = \App\Loader::getComponentClassName('Model', 'MappedFields', $moduleName);
		$mf = new $handlerClass();
		$mf->setData($row);
		\App\Runtime\Vtiger_Cache::set('MappedFieldsModel', $recordId, $mf);
		\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $mf;
	}

	public function setMapping($mapp = [])
	{
		$this->mapping = $mapp;
		return $this;
	}

	/**
	 * Function to get mapping details
	 * @return array list of mapping details
	 */
	public function getMapping()
	{

		\App\Log::trace('Entering ' . __METHOD__ . '() method ...');
		if (!$this->mapping) {
			$db = \App\database\PearDatabase::getInstance();
			$query = sprintf('SELECT * FROM %s WHERE %s = ?;', self::$mappingTable, self::$mappingIndex);
			$result = $db->pquery($query, [$this->getId()]);
			$mapping = $db->getArray($result);
			$finalMapping = [];
			if ($mapping) {
				foreach ($mapping as $mappingId => $mappingDetails) {
					$finalMapping[$mappingId] = [
						'type' => $mappingDetails['type'],
						'default' => $mappingDetails['default'],
						'source' => Settings_MappedFields_Field_Model::getInstance($mappingDetails['source'], $this->getModule(), $mappingDetails['type']),
						'target' => Settings_MappedFields_Field_Model::getInstance($mappingDetails['target'], $this->getRelatedModule(), $mappingDetails['type'])
					];
				}
			}
			$this->mapping = $finalMapping;
		}
		\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $this->mapping;
	}

	/**
	 * Function returns valuetype of the field filter
	 * @return string
	 */
	public function getFieldFilterValueType($fieldname)
	{
		$conditions = $this->get('conditions');
		if (!empty($conditions) && is_array($conditions)) {
			foreach ($conditions as $filter) {
				if ($fieldname == $filter['fieldname']) {
					return $filter['valuetype'];
				}
			}
		}
		return false;
	}

	public function getName()
	{
		return \vtlib\Functions::getModuleName($this->get('tabid'));
	}

	public function getRelatedName()
	{
		return \vtlib\Functions::getModuleName($this->get('reltabid'));
	}

	/**
	 * Function to check filters for record
	 * @param int $recordId
	 * @return boolean
	 */
	public function checkFiltersForRecord($recordId)
	{
		$key = $this->getId() . '_' . $recordId;
		if (\App\Cache::staticHas(__METHOD__, $key)) {
			return \App\Cache::staticGet(__METHOD__, $key);
		}
		require_once ROOT_DIRECTORY . '/src/Modules/com_vtiger_workflow/VTJsonCondition.php';
		$conditionStrategy = new \App\Modules\com_vtiger_workflow\VTJsonCondition();
		$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId);
		$test = $conditionStrategy->evaluate($this->getRaw('conditions'), $recordModel);
		\App\Cache::staticSave(__METHOD__, $key, $test);
		return $test;
	}

	public function checkUserPermissions()
	{

		\App\Log::trace('Entering ' . __METHOD__ . '() method ...');
		$permissions = $this->get('permissions');
		if (empty($permissions)) {
			\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
			return true;
		}
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$permissions = explode(',', $permissions);
		$getTypes = [];
		$return = false;
		foreach ($permissions as $name) {
			$valueType = explode(':', $name);
			$getTypes[$valueType[0]][] = $valueType[1];
		}
		if (in_array('Users:' . $currentUser->getId(), $permissions)) {
			$return = true;
		} elseif (in_array('Roles:' . $currentUser->getRole(), $permissions)) {
			$return = true;
		} elseif (array_key_exists('Groups', $getTypes)) {
			$accessibleGroups = array_keys(\App\Fields\Owner::getInstance($this->get('module_name'), $currentUser)->getAccessibleGroupForModule());
			$groups = array_intersect($getTypes['Groups'], $currentUser->getGroups());
			if (array_intersect($groups, $accessibleGroups)) {
				$return = true;
			}
		}
		if (array_key_exists('RoleAndSubordinates', $getTypes) && !$return) {
			$roles = $currentUser->getParentRoles();
			$roles[] = $currentUser->getRole();
			if (array_intersect($getTypes['RoleAndSubordinates'], array_filter($roles))) {
				$return = true;
			}
		}
		\App\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $return;
	}
}
