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

/**
 * Cron adapter class.
 * 
 * Backward compatibility adapter for vtlib\Cron.
 * Provides cron task management functionality.
 * 
 * @deprecated Use App\ModuleManagement\Services\CronService instead
 */
class Cron
{
	/** Status constants */
	const STATUS_DISABLED = 0;
	const STATUS_ENABLED = 1;
	const STATUS_RUNNING = 2;
	const STATUS_COMPLETED = 3;

	/** @var bool Static flag to indicate cron action */
	private static $cronAction = false;

	/** @var array Task data */
	private $data = [];

	/** @var \App\Db Database instance */
	private $db;

	/** @var string Table name */
	private $tableName = 'vtiger_cron_task';

	/**
	 * Constructor.
	 * 
	 * @param array $data Task data from database
	 */
	public function __construct(array $data = [])
	{
		$this->data = $data;
		$this->db = \App\Db\Db::getInstance();
	}

	/**
	 * Set cron action flag.
	 * 
	 * @param bool $value
	 * @return void
	 */
	public static function setCronAction(bool $value): void
	{
		self::$cronAction = $value;
	}

	/**
	 * Check if cron action is active.
	 * 
	 * @return bool
	 */
	public static function isCronAction(): bool
	{
		return self::$cronAction;
	}

	/**
	 * Get cron task instance by name.
	 * 
	 * Tries multiple strategies:
	 * 1. Exact name match
	 * 2. Handler file name match (e.g., "PrivilegesUpdater" matches "PrivilegesUpdater.php")
	 * 3. Partial name match (e.g., "PrivilegesUpdater" matches "LBL_PRIVILEGES_UPDATER")
	 * 
	 * @param string $name Task name or handler file name
	 * @return self|null
	 */
	public static function getInstance(string $name): ?self
	{
		$db = \App\Db\Db::getInstance();
		
		// Try exact name match first
		$row = (new \App\Db\Query())
			->from('vtiger_cron_task')
			->where(['name' => $name])
			->one();

		if ($row) {
			return new self($row);
		}

		// Try handler file name match (e.g., "PrivilegesUpdater" -> "PrivilegesUpdater.php")
		// Search for the name in handler_file path
		$row = (new \App\Db\Query())
			->from('vtiger_cron_task')
			->where(['like', 'handler_file', '%' . $name . '%'])
			->one();

		if ($row) {
			return new self($row);
		}

		// Try partial name match (case-insensitive)
		// Convert camelCase to UPPER_CASE (e.g., "PrivilegesUpdater" -> "PRIVILEGES_UPDATER")
		$nameUpper = strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
		
		// Try with LBL_ prefix
		$row = (new \App\Db\Query())
			->from('vtiger_cron_task')
			->where(['name' => 'LBL_' . $nameUpper])
			->one();

		if ($row) {
			return new self($row);
		}
		
		// Try partial match anywhere in name (case-insensitive)
		$row = (new \App\Db\Query())
			->from('vtiger_cron_task')
			->where(['like', 'name', '%' . $nameUpper . '%'])
			->one();

		if ($row) {
			return new self($row);
		}

		return null;
	}

	/**
	 * List all active cron task instances.
	 * 
	 * @return array Array of Cron instances
	 */
	public static function listAllActiveInstances(): array
	{
		$db = \App\Db\Db::getInstance();
		$dataReader = (new \App\Db\Query())
			->from('vtiger_cron_task')
			->where(['status' => self::STATUS_ENABLED])
			->orderBy(['sequence' => SORT_ASC])
			->createCommand()
			->query();

		$instances = [];
		while ($row = $dataReader->read()) {
			$instances[] = new self($row);
		}

		return $instances;
	}

	/**
	 * Register a cron task.
	 * 
	 * @param string $name Task name
	 * @param string $handler Handler file path
	 * @param int|string $frequency Frequency in seconds
	 * @param string $moduleName Module name
	 * @param int $status Status (STATUS_ENABLED or STATUS_DISABLED)
	 * @param int $sequence Sequence number (0 to auto-generate)
	 * @param string $description Description
	 * @return void
	 */
	public static function register(
		string $name,
		string $handler,
		$frequency,
		string $moduleName,
		int $status = self::STATUS_ENABLED,
		int $sequence = 0,
		string $description = ''
	): void {
		$db = \App\Db\Db::getInstance();

		// Auto-generate sequence if not provided
		if ($sequence == 0) {
			$sequence = $db->getUniqueID('vtiger_cron_task', 'sequence', false);
		}

		$db->createCommand()->insert('vtiger_cron_task', [
			'name' => $name,
			'handler_file' => $handler,
			'frequency' => (int) $frequency,
			'status' => $status,
			'sequence' => $sequence,
			'module' => $moduleName,
			'description' => $description
		])->execute();
	}

	/**
	 * Deregister a cron task.
	 * 
	 * @param string $name Task name
	 * @return void
	 */
	public static function deregister(string $name): void
	{
		$db = \App\Db\Db::getInstance();
		$db->createCommand()
			->delete('vtiger_cron_task', ['name' => $name])
			->execute();
	}

	/**
	 * Delete all cron tasks for a module.
	 * 
	 * @param string $moduleName Module name
	 * @return void
	 */
	public static function deleteForModule(string $moduleName): void
	{
		$db = \App\Db\Db::getInstance();
		$db->createCommand()
			->delete('vtiger_cron_task', ['module' => $moduleName])
			->execute();
	}

	/**
	 * Get task name.
	 * 
	 * @return string
	 */
	public function getName(): string
	{
		return $this->data['name'] ?? '';
	}

	/**
	 * Get handler file path.
	 * 
	 * @return string
	 */
	public function getHandlerFile(): string
	{
		return $this->data['handler_file'] ?? '';
	}

	/**
	 * Check if task had timeout (started but never finished).
	 * 
	 * @return bool|int Returns timestamp of laststart if timeout occurred, false otherwise
	 */
	public function hadTimeout()
	{
		$lastend = isset($this->data['lastend']) ? (int) $this->data['lastend'] : 0;
		$laststart = isset($this->data['laststart']) ? (int) $this->data['laststart'] : 0;

		if ($lastend === 0 && $laststart != 0) {
			return $laststart;
		}

		return false;
	}

	/**
	 * Check if task is currently running.
	 * 
	 * @return bool
	 */
	public function isRunning(): bool
	{
		return isset($this->data['status']) && (int) $this->data['status'] === self::STATUS_RUNNING;
	}

	/**
	 * Check if task is runnable (enabled and frequency time has passed).
	 * 
	 * @return bool
	 */
	public function isRunnable(): bool
	{
		// Must be enabled
		if (!isset($this->data['status']) || (int) $this->data['status'] !== self::STATUS_ENABLED) {
			return false;
		}

		// Must not be running
		if ($this->isRunning()) {
			return false;
		}

		// Check frequency
		$frequency = isset($this->data['frequency']) ? (int) $this->data['frequency'] : 0;
		$laststart = isset($this->data['laststart']) ? (int) $this->data['laststart'] : 0;
		$currentTime = time();

		// If never started, it's runnable
		if ($laststart == 0) {
			return true;
		}

		// Check if enough time has passed since last start
		return ($currentTime - $laststart) >= $frequency;
	}

	/**
	 * Mark task as running.
	 * 
	 * @return void
	 */
	public function markRunning(): void
	{
		$currentTime = time();
		$this->db->createCommand()
			->update('vtiger_cron_task', [
				'status' => self::STATUS_RUNNING,
				'laststart' => $currentTime
			], ['id' => $this->data['id']])
			->execute();

		// Update local data
		$this->data['status'] = self::STATUS_RUNNING;
		$this->data['laststart'] = $currentTime;
	}

	/**
	 * Mark task as finished.
	 * 
	 * @return void
	 */
	public function markFinished(): void
	{
		$currentTime = time();
		$this->db->createCommand()
			->update('vtiger_cron_task', [
				'status' => self::STATUS_ENABLED,
				'lastend' => $currentTime
			], ['id' => $this->data['id']])
			->execute();

		// Update local data
		$this->data['status'] = self::STATUS_ENABLED;
		$this->data['lastend'] = $currentTime;
	}

	/**
	 * Unlock task (set status to enabled).
	 * 
	 * @return void
	 */
	public function unlockTask(): void
	{
		$this->db->createCommand()
			->update('vtiger_cron_task', [
				'status' => self::STATUS_ENABLED
			], ['id' => $this->data['id']])
			->execute();

		// Update local data
		$this->data['status'] = self::STATUS_ENABLED;
	}

	/**
	 * Update task status.
	 * 
	 * @param int $status New status
	 * @return void
	 */
	public function updateStatus(int $status): void
	{
		$this->db->createCommand()
			->update('vtiger_cron_task', [
				'status' => $status
			], ['id' => $this->data['id']])
			->execute();

		// Update local data
		$this->data['status'] = $status;
	}

	/**
	 * Set a data value.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set(string $key, $value): void
	{
		$this->data[$key] = $value;
	}

	/**
	 * Get a data value.
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(string $key, $default = null)
	{
		return $this->data[$key] ?? $default;
	}

	/**
	 * Get all task data.
	 * 
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}
}

