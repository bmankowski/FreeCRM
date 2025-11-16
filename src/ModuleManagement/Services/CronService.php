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

namespace App\ModuleManagement\Services;

use App\ModuleManagement\Models;

/**
 * CronService class.
 * 
 * Service for cron task operations.
 */
class CronService
{
	/** @var \App\Db Database instance */
	private $db;

	/** Status constants */
	const STATUS_DISABLED = 0;
	const STATUS_ENABLED = 1;
	const STATUS_RUNNING = 2;
	const STATUS_COMPLETED = 3;

	/** @var string Base table name */
	private $baseTable = 'vtiger_cron_task';

	/**
	 * Constructor.
	 * 
	 * @param \App\Db\Db $db
	 */
	public function __construct(\App\Db\Db $db)
	{
		$this->db = $db;
	}

	/**
	 * Register a cron task.
	 * 
	 * @param string $name Task name
	 * @param string $handler Handler file path
	 * @param string $frequency Frequency in seconds
	 * @param string $moduleName Module name
	 * @param int $status Status (STATUS_ENABLED or STATUS_DISABLED)
	 * @param int $sequence Sequence number
	 * @param string $description Description
	 * @return void
	 */
	public function register(string $name, string $handler, string $frequency, string $moduleName, int $status = self::STATUS_ENABLED, int $sequence = 0, string $description = ''): void
	{
		if (empty($sequence)) {
			$sequence = $this->nextSequence();
		}

		$this->db->createCommand()->insert($this->baseTable, [
			'name' => $name,
			'handler_file' => $handler,
			'frequency' => $frequency,
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
	public function deregister(string $name): void
	{
		$this->db->createCommand()
			->delete($this->baseTable, ['name' => $name])
			->execute();
	}

	/**
	 * Delete all cron tasks for a module.
	 * 
	 * @param string $moduleName Module name
	 * @return void
	 */
	public function deleteForModule(string $moduleName): void
	{
		$this->db->createCommand()
			->delete($this->baseTable, ['module' => $moduleName])
			->execute();
	}

	/**
	 * List all cron task instances for a module.
	 * 
	 * @param string $moduleName Module name
	 * @return array Array of cron task data
	 */
	public function listAllInstancesByModule(string $moduleName): array
	{
		$dataReader = (new \App\Db\Query())
			->from($this->baseTable)
			->where(['module' => $moduleName])
			->createCommand()
			->query();

		$instances = [];
		while ($row = $dataReader->read()) {
			$instances[] = [
				'id' => $row['id'],
				'name' => $row['name'],
				'handler_file' => $row['handler_file'],
				'frequency' => $row['frequency'],
				'status' => $row['status'],
				'sequence' => $row['sequence'],
				'module' => $row['module'],
				'description' => $row['description'],
				'laststart' => $row['laststart'] ?? null,
				'lastend' => $row['lastend'] ?? null,
			];
		}

		return $instances;
	}

	/**
	 * Get next sequence number.
	 * 
	 * @return int Next sequence number
	 */
	public function nextSequence(): int
	{
		return $this->db->getUniqueID($this->baseTable, 'sequence', false);
	}

	/**
	 * Export cron tasks to XML.
	 * 
	 * @param Models\Module $module Module instance
	 * @param resource $manifestHandle Manifest file handle
	 * @return void
	 */
	public function exportToXML(Models\Module $module, $manifestHandle): void
	{
		$cronTasks = $this->listAllInstancesByModule($module->getName());
		if (empty($cronTasks)) {
			return;
		}

		$this->writeNode($manifestHandle, 'crons', '', true);
		foreach ($cronTasks as $cronTask) {
			$this->writeNode($manifestHandle, 'cron', '', true);
			$this->writeNode($manifestHandle, 'name', $cronTask['name']);
			$this->writeNode($manifestHandle, 'frequency', $cronTask['frequency']);
			$this->writeNode($manifestHandle, 'status', $cronTask['status'] == self::STATUS_ENABLED ? self::STATUS_ENABLED : self::STATUS_DISABLED);
			$this->writeNode($manifestHandle, 'handler', $cronTask['handler_file']);
			$this->writeNode($manifestHandle, 'sequence', $cronTask['sequence']);
			$this->writeNode($manifestHandle, 'description', $cronTask['description']);
			$this->writeNode($manifestHandle, 'cron', '', false);
		}
		$this->writeNode($manifestHandle, 'crons', '', false);
	}

	/**
	 * Write XML node to manifest handle.
	 * 
	 * @param resource $handle File handle
	 * @param string $node Node name
	 * @param mixed $value Node value
	 * @param bool $open Whether to open or close node
	 * @return void
	 */
	private function writeNode($handle, string $node, $value = '', bool $open = true): void
	{
		if ($open) {
			if ($value !== '') {
				fwrite($handle, "<$node>" . htmlspecialchars((string) $value, ENT_XML1, 'UTF-8') . "</$node>\n");
			} else {
				fwrite($handle, "<$node>\n");
			}
		} else {
			fwrite($handle, "</$node>\n");
		}
	}
}



