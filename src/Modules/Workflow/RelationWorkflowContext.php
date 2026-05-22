<?php
/**
 * FreeCRM - Context for relation modification workflows.
 *
 * @package   FreeCRM
 * @author    bmankowski@gmail.com
 * @license   FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Workflow;

class RelationWorkflowContext
{
	private string $sourceModule;
	private int $sourceRecordId;
	private string $destinationModule;
	private int $destinationRecordId;
	private string $relationTable;
	private string $relationField;
	private array $relationDataBefore;
	private array $relationDataAfter;
	private string $sourceStatus;
	private string $destinationStatus;
	private int $triggerUserId;
	private ?\App\Modules\Base\Models\Record $sourceRecordModel = null;
	private ?\App\Modules\Base\Models\Record $destinationRecordModel = null;

	public function __construct(
		string $sourceModule,
		int $sourceRecordId,
		string $destinationModule,
		int $destinationRecordId,
		string $relationTable,
		string $relationField,
		array $relationDataBefore,
		array $relationDataAfter,
		string $sourceStatus,
		string $destinationStatus,
		int $triggerUserId
	) {
		$this->sourceModule = $sourceModule;
		$this->sourceRecordId = $sourceRecordId;
		$this->destinationModule = $destinationModule;
		$this->destinationRecordId = $destinationRecordId;
		$this->relationTable = $relationTable;
		$this->relationField = $relationField;
		$this->relationDataBefore = $relationDataBefore;
		$this->relationDataAfter = $relationDataAfter;
		$this->sourceStatus = $sourceStatus;
		$this->destinationStatus = $destinationStatus;
		$this->triggerUserId = $triggerUserId;
	}

	public function getSourceModule(): string
	{
		return $this->sourceModule;
	}

	public function getSourceRecordId(): int
	{
		return $this->sourceRecordId;
	}

	public function getDestinationModule(): string
	{
		return $this->destinationModule;
	}

	public function getDestinationRecordId(): int
	{
		return $this->destinationRecordId;
	}

	public function getRelationTable(): string
	{
		return $this->relationTable;
	}

	public function getRelationField(): string
	{
		return $this->relationField;
	}

	public function getSourceStatus(): string
	{
		return $this->sourceStatus;
	}

	public function getDestinationStatus(): string
	{
		return $this->destinationStatus;
	}

	public function getSourceRecordModel(): \App\Modules\Base\Models\Record
	{
		if ($this->sourceRecordModel === null) {
			$this->sourceRecordModel = \App\Modules\Base\Models\Record::getInstanceById($this->sourceRecordId, $this->sourceModule);
		}
		return $this->sourceRecordModel;
	}

	public function getDestinationRecordModel(): \App\Modules\Base\Models\Record
	{
		if ($this->destinationRecordModel === null) {
			$this->destinationRecordModel = \App\Modules\Base\Models\Record::getInstanceById($this->destinationRecordId, $this->destinationModule);
		}
		return $this->destinationRecordModel;
	}

	public function getRelationValue(string $fieldName): mixed
	{
		return $this->relationDataAfter[$fieldName] ?? $this->relationDataBefore[$fieldName] ?? null;
	}

	public function toParams(): array
	{
		return [
			'sourceModule' => $this->sourceModule,
			'sourceRecordId' => $this->sourceRecordId,
			'destinationModule' => $this->destinationModule,
			'destinationRecordId' => $this->destinationRecordId,
			'relationTable' => $this->relationTable,
			'relationField' => $this->relationField,
			'sourceStatus' => $this->sourceStatus,
			'destinationStatus' => $this->destinationStatus,
			'triggerUserId' => $this->triggerUserId,
		];
	}
}
