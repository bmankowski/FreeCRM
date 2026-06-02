<?php
namespace App\Events;

use App\Cache\Cache;

/**
 * Event Handler main class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class EventHandler
{

	/**
	 * Table name
	 * @var string 
	 */
	protected static $baseTable = 'vtiger_eventhandlers';
	private static $handlerByType;
	private $recordModel;
	private $moduleName;
	private $params;
	private $userId;
	private static $handlersInstance;
	private $exceptions;
	private static $mandatoryEventClass = ['ModTracker_ModTrackerHandler_Handler', 'Vtiger_RecordLabelUpdater_Handler'];
	
	/**
	 * Map of legacy class names to modern namespaced class names
	 * @var array
	 */
	private static $classNameMap = [
		'ModTracker_ModTrackerHandler_Handler' => '\App\Modules\ModTracker\Handlers\ModTracker_ModTrackerHandler_Handler',
		'Vtiger_RecordLabelUpdater_Handler' => '\App\Modules\Base\Handlers\Vtiger_RecordLabelUpdater_Handler',
		'Vtiger_Workflow_Handler' => '\App\Modules\Base\Handlers\Vtiger_Workflow_Handler',
		'Vtiger_MultiReferenceUpdater_Handler' => '\App\Modules\Base\Handlers\Vtiger_MultiReferenceUpdater_Handler',
		'Vtiger_AutomaticAssignment_Handler' => '\App\Modules\Base\Handlers\Vtiger_AutomaticAssignment_Handler',
		'Vtiger_SharingPrivileges_Handler' => '\App\Modules\Base\Handlers\Vtiger_SharingPrivileges_Handler',
		'Vtiger_Attachments_Handler' => '\App\Modules\Base\Handlers\Vtiger_Attachments_Handler',
		'Calendar_CalendarHandler_Handler' => '\App\Modules\Calendar\Handlers\Calendar_CalendarHandler_Handler',
		'API_CalDAV_Handler' => '\App\Modules\API\Handlers\API_CalDAV_Handler',
		'API_CardDAV_Handler' => '\App\Modules\API\Handlers\API_CardDAV_Handler',
		'Users_ForgotPassword_Handler' => '\App\Modules\Users\Handlers\Users_ForgotPassword_Handler',
		'OSSPasswords_Secure_Handler' => '\App\Modules\OSSPasswords\Handlers\OSSPasswords_Secure_Handler',
		'Accounts_SaveChanges_Handler' => '\App\Modules\Accounts\Handlers\Accounts_SaveChanges_Handler',
		'ServiceContracts_ServiceContractsHandler_Handler' => '\App\Modules\ServiceContracts\Handlers\ServiceContracts_ServiceContractsHandler_Handler',
		'OpenStreetMap_OpenStreetMapHandler_Handler' => '\App\Modules\OpenStreetMap\Handlers\OpenStreetMap_OpenStreetMapHandler_Handler',
		'ProjectTask_ProjectTaskHandler_Handler' => '\App\Modules\ProjectTask\Handlers\ProjectTask_ProjectTaskHandler_Handler',
		'PBXManager_PBXManagerHandler_Handler' => '\App\Modules\PBXManager\Handlers\PBXManager_PBXManagerHandler_Handler',
		'OSSTimeControl_TimeControl_Handler' => '\App\Modules\OSSTimeControl\Handlers\OSSTimeControl_TimeControl_Handler',
		'HelpDesk_TicketRangeTime_Handler' => '\App\Modules\HelpDesk\Handlers\HelpDesk_TicketRangeTime_Handler',
		'IStorages_RecalculateStockHandler_Handler' => '\App\Modules\IStorages\Handlers\IStorages_RecalculateStockHandler_Handler',
		'ProjektyRekrutacyjne_Calculations_Handler' => '\App\Modules\ProjektyRekrutacyjne\Handlers\Calculations',
		'Kandydaci_NewCandidateInProject_Handler' => '\App\Modules\Kandydaci\Handlers\NewCandidateInProject',
	];

	/**
	 * Get all event handlers
	 * @param boolean $active true/false
	 * @return array
	 */
	public static function getAll($active = true)
	{
		if (Cache::has('EventHandler', 'All')) {
			$handlers = Cache::get('EventHandler', 'All');
		} else {
			$handlers = (new \App\Db\Query())->from(self::$baseTable)->orderBy(['priority' => SORT_DESC])->all();
			Cache::save('EventHandler', 'All', $handlers);
		}
		if ($active) {
			foreach ($handlers as $key => &$handler) {
				if ($handler['is_active'] !== 1) {
					unset($handlers[$key]);
				}
			}
		}
		return $handlers;
	}

	/**
	 * Get active event handlers by type (event_name)
	 * @param string $name
	 * @return array
	 */
	public static function getByType($name, $moduleName = false)
	{
		if (!isset(self::$handlerByType)) {
			$handlers = [];
			foreach (static::getAll(true) as &$handler) {
				$handlers[$handler['event_name']][$handler['handler_class']] = $handler;
			}
			static::$handlerByType = $handlers;
		}
		$handlers = isset(static::$handlerByType[$name]) ? static::$handlerByType[$name] : [];
		if ($moduleName) {
			foreach ($handlers as $key => &$handler) {
				if ((!empty($handler['include_modules']) && !in_array($moduleName, explode(',', $handler['include_modules']))) || (!empty($handler['exclude_modules']) && in_array($moduleName, explode(',', $handler['exclude_modules'])))) {
					unset($handlers[$key]);
				}
			}
		}
		return $handlers;
	}

	/**
	 * Register an event handler
	 * @param string $eventName The name of the event to handle
	 * @param string $className
	 * @param string $includeModules
	 * @param string $excludeModules
	 * @param int $priority
	 * @param boolean $isActive
	 */
	public static function registerHandler($eventName, $className, $includeModules = '', $excludeModules = '', $priority = 5, $isActive = true, $ownerId = 0)
	{
		$isExists = (new \App\Db\Query())->from(self::$baseTable)->where(['event_name' => $eventName, 'handler_class' => $className])->exists();
		if (!$isExists) {
			\App\Db\Db::getInstance()->createCommand()
				->insert(self::$baseTable, [
					'event_name' => $eventName,
					'handler_class' => $className,
					'is_active' => $isActive,
					'include_modules' => $includeModules,
					'exclude_modules' => $excludeModules,
					'priority' => $priority,
					'owner_id' => $ownerId
				])->execute();
			static::clearCache();
		}
	}

	/**
	 * Clear cache
	 */
	public static function clearCache()
	{
		self::$handlerByType = null;
		Cache::delete('EventHandler', 'All');
	}

	/**
	 * Unregister a registered handler
	 * @param string $className 
	 * @param boolean|string $eventName 
	 */
	public static function deleteHandler($className, $eventName = false)
	{
		$params = ['handler_class' => $className];
		if ($eventName) {
			$params['event_name'] = $eventName;
		}
		\App\Db\Db::getInstance()->createCommand()->delete(self::$baseTable, $params)->execute();
		static::clearCache();
	}

	/**
	 * Set an event handler as inactive
	 * @param string $className 
	 * @param boolean|string $eventName 
	 */
	public static function setInActive($className, $eventName = false)
	{
		$params = ['handler_class' => $className];
		if ($eventName) {
			$params['event_name'] = $eventName;
		}
		\App\Db\Db::getInstance()->createCommand()
			->update(self::$baseTable, ['is_active' => false], $params)->execute();
		static::clearCache();
	}

	/**
	 * Set an event handler as active
	 * @param string $className 
	 * @param boolean|string $eventName 
	 */
	public static function setActive($className, $eventName = false)
	{
		$params = ['handler_class' => $className];
		if ($eventName) {
			$params['event_name'] = $eventName;
		}
		\App\Db\Db::getInstance()->createCommand()
			->update(self::$baseTable, ['is_active' => true], $params)->execute();
		static::clearCache();
	}

	/**
	 * Set record model
	 * @param \App\App\Modules\Base\Models\Record $recordModel
	 */
	public function setRecordModel($recordModel)
	{
		$this->recordModel = $recordModel;
	}

	/**
	 * Set module name
	 * @param string $moduleName
	 */
	public function setModuleName($moduleName)
	{
		$this->moduleName = $moduleName;
	}

	/**
	 * Set params
	 * @param array $params
	 */
	public function setParams($params)
	{
		$this->params = $params;
	}

	/**
	 * Add param
	 * @param array $params
	 */
	public function addParams($key, $value)
	{
		$this->params[$key] = $value;
	}

	/**
	 * Set user Id
	 * @param int $userId
	 */
	public function setUser($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * Get record model
	 * @return \App\Modules\Base\Models\Record
	 */
	public function getRecordModel()
	{
		return $this->recordModel;
	}

	/**
	 * Get module name
	 * @return string
	 */
	public function getModuleName()
	{
		return $this->moduleName;
	}

	/**
	 * Get params
	 * @return array Additional parameters
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Set exceptions
	 * @param array $exceptions
	 */
	public function setExceptions($exceptions)
	{
		$this->exceptions = $exceptions;
	}

	/**
	 * 	 * @param string $name Event name
	 * @return array Handlers list
	 */
	protected function getHandlers($name)
	{
		$handlers = static::getByType($name, $this->moduleName);
		if ($this->exceptions) {
			if (!empty($this->exceptions['disableHandlers'])) {
				$mandatory = [];
				foreach (static::$mandatoryEventClass as &$className) {
					if (isset($handlers[$className])) {
						$mandatory[$className] = $handlers[$className];
					}
				}
				unset($handlers);
				$handlers = $mandatory;
			}
			if (!empty($this->exceptions['disableWorkflow'])) {
				unset($handlers['Vtiger_Workflow_Handler']);
			}
			if (!empty($this->exceptions['disableHandlerByName'])) {
				foreach ($this->exceptions['disableHandlerByName'] as &$className) {
					unset($handlers[$className]);
				}
			}
		}
		return $handlers;
	}

	/**
	 * Trigger an event
	 * @param string $name Event name
	 * @throws \App\Exceptions\AppException
	 */
	public function trigger($name)
	{
		foreach ($this->getHandlers($name) as &$handler) {
			if (isset(static::$handlersInstance[$handler['handler_class']])) {
				$handlerInstance = static::$handlersInstance[$handler['handler_class']];
			} else {
				// Map legacy class names to modern namespaced class names
				$className = $handler['handler_class'];
				if (isset(static::$classNameMap[$className])) {
					$className = static::$classNameMap[$className];
				}
			$handlerInstance = new $className();
			static::$handlersInstance[$handler['handler_class']] = $handlerInstance;
		}
		// VTWorkflowEventHandler uses handleEvent() method with event name as parameter
		if (method_exists($handlerInstance, 'handleEvent')) {
			$handlerInstance->handleEvent($name, $this);
		} else {
			// Modern handlers use lcfirst event name as method
			$function = lcfirst($name);
			if (method_exists($handlerInstance, $function)) {
				$handlerInstance->$function($this);
			} else {
				throw new \App\Exceptions\AppException('LBL_HANDLER_NOT_FOUND');
			}
		}
		}
	}

	/**
	 * Set system handler
	 * @param string $name
	 * @return boolean
	 */
	public function setSystemTrigger($name, $class = '', $params = [])
	{
		$handlers = static::getByType($name, $this->moduleName);
		if (empty($handlers)) {
			return false;
		}
		$db = \App\Db\Db::getInstance('admin');
		$isExists = (new \App\Db\Query())->from('s_#__handler_updater')->where(['crmid' => $this->getRecordModel()->getId()])->exists($db);
		if (!$isExists) {
			$db->createCommand()
				->insert('s_#__handler_updater', [
					'tabid' => \App\Utils\ModuleUtils::getModuleId($this->getModuleName()),
					'crmid' => $this->getRecordModel()->getId(),
					'userid' => (int) (\App\User\CurrentUser::getId() ?? 0),
					'handler_name' => $name,
					'class' => $class,
					'params' => \App\Utils\Json::encode($params)
				])->execute();
		}
	}
}
