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

namespace vtlib;

use App\ModuleManagement\ServiceLocator;
use App\ModuleManagement\Models;
use App\Cache\Cache;

/**
 * Module adapter class.
 * 
 * Backward compatibility adapter for vtlib\Module.
 * Delegates to ModuleManagement services.
 * 
 * @deprecated Use App\ModuleManagement\Services\ModuleService instead
 */
class Module extends ModuleBasic
{
	/**
	 * Build ModuleManagement model from current adapter state.
	 *
	 * @return Models\Module
	 */
	private function toModuleModel(): Models\Module
	{
		$label = $this->label ?: $this->name;
		$presence = $this->presence ?? 0;
		$ownedby = $this->ownedby ?? 0;
		$customized = $this->customized ?? 1;
		$isentitytype = $this->isentitytype ?? true;
		$type = $this->type ?? 0;

		return new Models\Module(
			$this->id,
			$this->name,
			$label,
			(int) ($this->version ?? 0),
			$this->minversion,
			$this->maxversion,
			(int) $presence,
			(int) $ownedby,
			$this->tabsequence,
			$this->parent,
			(int) $customized,
			(bool) $isentitytype,
			$this->entityidcolumn,
			$this->entityidfield,
			$this->basetable,
			$this->basetableid,
			$this->customtable,
			$this->grouptable,
			(int) $type,
			$this->tableName
		);
	}

	/**
	 * Persist the module definition using ModuleService.
	 * @return int Created module ID
	 */
	public function save()
	{
		$moduleService = ServiceLocator::getModuleService();
		$moduleId = $moduleService->create($this->toModuleModel());
		$this->id = $moduleId;
		$this->customized = 1;

		$tabsequence = (new \App\Db\Query())
			->select('tabsequence')
			->from('vtiger_tab')
			->where(['tabid' => $moduleId])
			->scalar();
		if ($tabsequence !== false) {
			$this->tabsequence = (int) $tabsequence;
		}

		Cache::delete('moduleTabById', $moduleId);
		Cache::delete('moduleTabByName', $this->name);
		Cache::delete('moduleTabs', 'all');

		return $this->id;
	}

	/**
	 * Initialize module tables using ModuleService.
	 *
	 * @param string|false $basetable
	 * @param string|false $basetableid
	 * @return $this
	 */
	public function initTables($basetable = false, $basetableid = false)
	{
		if ($basetable) {
			$this->basetable = $basetable;
		}
		if ($basetableid) {
			$this->basetableid = $basetableid;
		}

		if (!$this->basetable) {
			$this->basetable = \App\ModuleManagement\Services\ModuleService::entityTableName($this->name);
		}
		if (!$this->basetableid) {
			$this->basetableid = \App\ModuleManagement\Services\ModuleService::entityIdColumn($this->name);
		}
		if (!$this->customtable) {
			$this->customtable = \App\ModuleManagement\Services\ModuleService::entityCustomTableName($this->name);
		}

		ServiceLocator::getModuleService()->initTables($this->id, $this->toModuleModel());

		return $this;
	}
	/**
	 * Function to get the Module/Tab id
	 * @return int|false
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Check if module is active (presence = 0).
	 *
	 * @return bool
	 */
	public function isActive(): bool
	{
		return (int) $this->presence === 0;
	}

	/**
	 * Get related list sequence to use
	 * @return int
	 */
	public function __getNextRelatedListSequence()
	{
		return (new \App\Db\Query())->from('vtiger_relatedlists')->where(['tabid' => $this->id])->max('sequence') + 1;
	}

	/**
	 * Set related list information between other module
	 * @param Module Instance of target module with which relation should be setup
	 * @param string Label to display in related list (default is target module name)
	 * @param array  List of action button to show ('ADD', 'SELECT')
	 * @param string Callback function name of this module to use as handler
	 */
	public function setRelatedList($moduleInstance, $label = '', $actions = false, $functionName = 'getRelatedList')
	{
		$relationService = ServiceLocator::getRelationService();
		$targetModuleId = is_object($moduleInstance) ? $moduleInstance->id : $moduleInstance;
		$relationService->setRelatedList($this->id, $targetModuleId, $label, $actions ?: ['ADD'], $functionName);
		self::log("Setting relation with " . (is_object($moduleInstance) ? $moduleInstance->name : 'module') . " ... DONE");
	}

	/**
	 * Unset related list information that exists with other module
	 * @param Module Instance of target module with which relation should be setup
	 * @param string Label to display in related list (default is target module name)
	 * @param string Callback function name of this module to use as handler
	 */
	public function unsetRelatedList($moduleInstance, $label = '', $function_name = 'getRelatedList')
	{
		if (empty($moduleInstance))
			return;

		if (empty($label) && is_object($moduleInstance))
			$label = $moduleInstance->name;

		$relationService = ServiceLocator::getRelationService();
		$targetModuleId = is_object($moduleInstance) ? $moduleInstance->id : $moduleInstance;
		$relationService->unsetRelatedList($this->id, $targetModuleId, $label, $function_name);
		self::log("Unsetting relation with " . (is_object($moduleInstance) ? $moduleInstance->name : 'module') . " ... DONE");
	}

	/**
	 * Add custom link for a module page
	 * @param string Type can be like 'DETAILVIEW', 'LISTVIEW' etc..
	 * @param string Label to use for display
	 * @param string HREF value to use for generated link
	 * @param string Path to the image file (relative or absolute)
	 * @param integer Sequence of appearance
	 */
	public function addLink($type, $label, $url, $iconpath = '', $sequence = 0, $handlerInfo = null)
	{
		$linkService = ServiceLocator::getLinkService();
		$linkService->addLink($this->id, $type, $label, $url, $iconpath, $sequence, $handlerInfo);
	}

	/**
	 * Delete custom link of a module
	 * @param string Type can be like 'DETAILVIEW', 'LISTVIEW' etc..
	 * @param string Display label to lookup
	 * @param string URL value to lookup
	 */
	public function deleteLink($type, $label, $url = false)
	{
		$linkService = ServiceLocator::getLinkService();
		$linkService->deleteLink($this->id, $type, $label, $url);
	}

	/**
	 * Get all the custom links related to this module.
	 */
	public function getLinks()
	{
		$linkService = ServiceLocator::getLinkService();
		return $linkService->getAll($this->id);
	}

	/**
	 * Get all the custom links related to this module for exporting.
	 */
	public function getLinksForExport()
	{
		$linkService = ServiceLocator::getLinkService();
		return $linkService->getAllForExport($this->id);
	}

	/**
	 * Initialize webservice setup for this module instance.
	 */
	public function initWebservice()
	{
		ServiceLocator::getModuleService()->initWebservice($this->id);
	}

	/**
	 * De-Initialize webservice setup for this module instance.
	 */
	public function deinitWebservice()
	{
		ServiceLocator::getWebserviceService()->uninitialize($this->name, (bool) ($this->isentitytype ?? true));
	}

	/**
	 * Create module files from templates
	 * @param object $entityField object with name, label, column properties
	 */
	public function createFiles(object $entityField)
	{
		$fileService = ServiceLocator::getFileService();
		$fileService->createModuleFiles($this, $entityField);
	}

	/**
	 * Add block to this module.
	 *
	 * @param Block $blockInstance
	 * @return $this
	 */
	public function addBlock($blockInstance)
	{
		if (is_object($blockInstance)) {
			$blockInstance->module = $this;
			$blockInstance->save($this);
		}
		return $this;
	}

	/**
	 * Add filter (custom view) to this module.
	 *
	 * @param Filter $filterInstance
	 * @return $this
	 */
	public function addFilter($filterInstance)
	{
		if (is_object($filterInstance)) {
			$filterInstance->save($this);
		}
		return $this;
	}

	/**
	 * Set entity identifier field.
	 *
	 * @param Field $fieldInstance
	 * @return $this
	 */
	public function setEntityIdentifier($fieldInstance)
	{
		$fieldId = null;
		if (is_object($fieldInstance) && isset($fieldInstance->id)) {
			$fieldId = (int) $fieldInstance->id;
		}
		if (!$fieldId) {
			throw new \RuntimeException('Field instance must be saved before setting as entity identifier');
		}

		$this->entityidfield = $this->entityidfield ?: $this->basetableid;
		$this->entityidcolumn = $this->entityidcolumn ?: $this->basetableid;

		if (!$this->entityidfield || !$this->entityidcolumn) {
			throw new \RuntimeException('Module entity identifiers are not defined');
		}

		$fieldTable = isset($fieldInstance->table) ? $fieldInstance->table : '';
		if (empty($fieldTable)) {
			$fieldTable = $this->basetable ?: '';
		}
		$fieldName = isset($fieldInstance->name) ? $fieldInstance->name : '';

		$db = \App\Db\Db::getInstance();
		$isExists = (new \App\Db\Query())
			->from('vtiger_entityname')
			->where(['tabid' => $this->id])
			->exists();

		if (!$isExists) {
			$db->createCommand()->insert('vtiger_entityname', [
				'tabid' => $this->id,
				'modulename' => $this->name,
				'tablename' => $fieldTable,
				'fieldname' => $fieldName,
				'entityidfield' => $this->entityidfield,
				'entityidcolumn' => $this->entityidcolumn,
				'searchcolumn' => $fieldName
			])->execute();
		} else {
			$db->createCommand()->update('vtiger_entityname', [
				'modulename' => $this->name,
				'fieldname' => $fieldName,
				'entityidfield' => $this->entityidfield,
				'entityidcolumn' => $this->entityidcolumn,
				'tablename' => $fieldTable,
				'searchcolumn' => $fieldName
			], ['tabid' => $this->id])->execute();
		}

		return $this;
	}

	/**
	 * Configure default sharing access for the module.
	 *
	 * @param string $permissionText
	 * @return $this
	 */
	public function setDefaultSharing($permissionText = 'Public_ReadWriteDelete')
	{
		$accessService = ServiceLocator::getAccessService();
		$accessService->setDefaultSharing($this->id, $permissionText);
		return $this;
	}

	/**
	 * Enable tools for the module.
	 *
	 * @param string|array $tools
	 * @return $this
	 */
	public function enableTools($tools)
	{
		$tools = is_array($tools) ? $tools : [$tools];
		$accessService = ServiceLocator::getAccessService();
		foreach ($tools as $tool) {
			$accessService->updateTool($this->id, $tool, true);
		}
		return $this;
	}

	/**
	 * Disable tools for the module.
	 *
	 * @param string|array $tools
	 * @return $this
	 */
	public function disableTools($tools)
	{
		$tools = is_array($tools) ? $tools : [$tools];
		$accessService = ServiceLocator::getAccessService();
		foreach ($tools as $tool) {
			$accessService->updateTool($this->id, $tool, false);
		}
		return $this;
	}

	/**
	 * Get instance by id or name
	 * @param mixed id or name of the module
	 * @return self|false
	 */
	public static function getInstance($value)
	{
		$moduleService = ServiceLocator::getModuleService();
		$module = $moduleService->getInstance($value);
		
		if (!$module) {
			return false;
		}
		
		$instance = new self();
		$instance->id = $module->getId();
		$instance->name = $module->getName();
		$instance->label = $module->getLabel();
		$instance->version = $module->getVersion();
		$instance->minversion = $module->getMinversion();
		$instance->maxversion = $module->getMaxversion();
		$instance->presence = $module->getPresence();
		$instance->ownedby = $module->getOwnedby();
		$instance->tabsequence = $module->getTabsequence();
		$instance->parent = $module->getParent();
		$instance->customized = $module->getCustomized();
		$instance->isentitytype = $module->getIsentitytype();
		$instance->entityidcolumn = $module->getEntityidcolumn();
		$instance->entityidfield = $module->getEntityidfield();
		$instance->basetable = $module->getBasetable();
		$instance->basetableid = $module->getBasetableid();
		$instance->customtable = $module->getCustomtable();
		$instance->grouptable = $module->getGrouptable();
		$instance->type = $module->getType();
		
		return $instance;
	}

	/**
	 * Get instance of the module class.
	 * @param string Module name
	 * @return object|false
	 */
	public static function getClassInstance($modulename)
	{
		if ($modulename == 'Calendar')
			$modulename = 'Activity';

		$instance = false;
		$filepath = "modules/$modulename/$modulename.php";
		$fileService = ServiceLocator::getFileService();
		if ($fileService->checkFileAccessForInclusion($filepath, false)) {
			Deprecated::checkFileAccessForInclusion($filepath);
			include_once($filepath);
			if (class_exists($modulename)) {
				$instance = new $modulename();
			}
		}
		return $instance;
	}

	/**
	 * Fire the event for the module (if vtlib_handler is defined)
	 * @param string $modulename
	 * @param string $eventType
	 * @return bool
	 */
	public static function fireEvent($modulename, $eventType)
	{
		$eventDispatcher = ServiceLocator::getEventDispatcher();
		return $eventDispatcher->fire($modulename, $eventType);
	}

	/**
	 * Toggle the module (enable/disable)
	 * @param string $moduleName
	 * @param bool $enableDisable
	 */
	public static function toggleModuleAccess($moduleName, $enableDisable)
	{
		$moduleService = ServiceLocator::getModuleService();
		$moduleService->toggleAccess($moduleName, $enableDisable === true);
	}

	/**
	 * Helper function to log messages
	 * @param string $message Message to log
	 * @param bool $delimit true appends linebreak, false to avoid it
	 */
	static function log($message, $delimit = true)
	{
		\vtlib\Utils::Log($message, $delimit);
	}
}

