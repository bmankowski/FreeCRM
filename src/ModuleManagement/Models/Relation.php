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

namespace App\ModuleManagement\Models;

/**
 * Relation value object.
 * 
 * Immutable representation of a module relationship.
 */
class Relation
{
	/** @var int Source module ID */
	private $sourceModuleId;
	
	/** @var int Target module ID */
	private $targetModuleId;
	
	/** @var string Label */
	private $label;
	
	/** @var array Actions (e.g., ['ADD', 'SELECT']) */
	private $actions;
	
	/** @var string Function name */
	private $functionName;
	
	/** @var int Sequence */
	private $sequence;
	
	/** @var int Presence (0 = enabled, 1 = disabled) */
	private $presence;

	/**
	 * Constructor.
	 * 	 * @param int $sourceModuleId
	 * @param int $targetModuleId
	 * @param string $label
	 * @param array $actions
	 * @param string $functionName
	 * @param int $sequence
	 * @param int $presence
	 */
	public function __construct(
		$sourceModuleId,
		$targetModuleId,
		$label = '',
		$actions = [],
		$functionName = 'getRelatedList',
		$sequence = null,
		$presence = 0
	) {
		$this->sourceModuleId = $sourceModuleId;
		$this->targetModuleId = $targetModuleId;
		$this->label = $label;
		$this->actions = $actions;
		$this->functionName = $functionName;
		$this->sequence = $sequence;
		$this->presence = $presence;
	}

	public function getSourceModuleId() { return $this->sourceModuleId; }
	public function getTargetModuleId() { return $this->targetModuleId; }
	public function getLabel() { return $this->label; }
	public function getActions() { return $this->actions; }
	public function getFunctionName() { return $this->functionName; }
	public function getSequence() { return $this->sequence; }
	public function getPresence() { return $this->presence; }
}





