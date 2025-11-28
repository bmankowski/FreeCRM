<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Value object describing a queued ImportManager task.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Jobs;

class ImportJob
{
	public const STATUS_NONE = 0;
	public const STATUS_SCHEDULED = 1;
	public const STATUS_RUNNING = 2;
	public const STATUS_HALTED = 3;
	public const STATUS_COMPLETED = 4;

	private int $id;
	private int $userId;
	private int $tabId;
	private int $status;
	private array $payload;

	private function __construct(int $id, int $userId, int $tabId, int $status, array $payload)
	{
		$this->id = $id;
		$this->userId = $userId;
		$this->tabId = $tabId;
		$this->status = $status;
		$this->payload = $payload;
	}

	public static function fromRow(?array $row): self
	{
		if (!$row) {
			throw new \RuntimeException('Nie można odczytać danych zadania kolejki.');
		}

		$payload = \App\Utils\Json::decode($row['field_mapping'] ?? '') ?? [];

		return new self(
			(int) $row['importid'],
			(int) $row['userid'],
			(int) $row['tabid'],
			(int) $row['temp_status'],
			$payload
		);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function getTabId(): int
	{
		return $this->tabId;
	}

	public function getStatus(): int
	{
		return $this->status;
	}

	public function getType(): string
	{
		return $this->payload['type'] ?? 'stage';
	}

	public function getBatchId(): int
	{
		return (int) ($this->payload['batchId'] ?? 0);
	}
}

