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
 * Module value object.
 * 
 * Immutable representation of a module with all properties from ModuleBasic.
 */
class Module
{
	/** @var int|false Module ID */
	private $id;

	/** @var string|false Module name */
	private $name;

	/** @var string|false Module label */
	private $label;

	/** @var int Module version */
	private $version;

	/** @var string|false Minimum version */
	private $minversion;

	/** @var string|false Maximum version */
	private $maxversion;

	/** @var int Presence (0 = enabled, 1 = disabled) */
	private $presence;

	/** @var int Owned by (0 = Sharing Access Enabled, 1 = Sharing Access Disabled) */
	private $ownedby;

	/** @var int|false Tab sequence */
	private $tabsequence;

	/** @var string|false Parent module */
	private $parent;

	/** @var int Customized flag (0 = standard, 1 = custom) */
	private $customized;

	/** @var bool Is entity type (true = real module, false = extension) */
	private $isentitytype;

	/** @var string|false Entity ID column */
	private $entityidcolumn;

	/** @var string|false Entity ID field */
	private $entityidfield;

	/** @var string|false Base table name */
	private $basetable;

	/** @var string|false Base table ID column */
	private $basetableid;

	/** @var string|false Custom table name */
	private $customtable;

	/** @var string|false Group table name */
	private $grouptable;

	/** @var int Module type (0 = entity, 1 = inventory) */
	private $type;

	/** @var string|null Table name */
	private $tableName;

	/**
	 * Constructor.
	 * 
	 * @param int|false $id
	 * @param string|false $name
	 * @param string|false $label
	 * @param int $version
	 * @param string|false $minversion
	 * @param string|false $maxversion
	 * @param int $presence
	 * @param int $ownedby
	 * @param int|false $tabsequence
	 * @param string|false $parent
	 * @param int $customized
	 * @param bool $isentitytype
	 * @param string|false $entityidcolumn
	 * @param string|false $entityidfield
	 * @param string|false $basetable
	 * @param string|false $basetableid
	 * @param string|false $customtable
	 * @param string|false $grouptable
	 * @param int $type
	 * @param string|null $tableName
	 */
	public function __construct(
		$id = false,
		$name = false,
		$label = false,
		$version = 0,
		$minversion = false,
		$maxversion = false,
		$presence = 0,
		$ownedby = 0,
		$tabsequence = false,
		$parent = false,
		$customized = 0,
		$isentitytype = true,
		$entityidcolumn = false,
		$entityidfield = false,
		$basetable = false,
		$basetableid = false,
		$customtable = false,
		$grouptable = false,
		$type = 0,
		$tableName = null
	) {
		$this->id = $id;
		$this->name = $name;
		$this->label = $label;
		$this->version = $version;
		$this->minversion = $minversion;
		$this->maxversion = $maxversion;
		$this->presence = $presence;
		$this->ownedby = $ownedby;
		$this->tabsequence = $tabsequence;
		$this->parent = $parent;
		$this->customized = $customized;
		$this->isentitytype = $isentitytype;
		$this->entityidcolumn = $entityidcolumn;
		$this->entityidfield = $entityidfield;
		$this->basetable = $basetable;
		$this->basetableid = $basetableid;
		$this->customtable = $customtable;
		$this->grouptable = $grouptable;
		$this->type = $type;
		$this->tableName = $tableName;
	}

	public function getId()
	{
		return $this->id;
	}
	public function getName()
	{
		return $this->name;
	}
	public function getLabel()
	{
		return $this->label;
	}
	public function getVersion()
	{
		return $this->version;
	}
	public function getMinversion()
	{
		return $this->minversion;
	}
	public function getMaxversion()
	{
		return $this->maxversion;
	}
	public function getPresence()
	{
		return $this->presence;
	}
	public function getOwnedby()
	{
		return $this->ownedby;
	}
	public function getTabsequence()
	{
		return $this->tabsequence;
	}
	public function getParent()
	{
		return $this->parent;
	}
	public function getCustomized()
	{
		return $this->customized;
	}
	public function getIsentitytype()
	{
		return $this->isentitytype;
	}
	public function getEntityidcolumn()
	{
		return $this->entityidcolumn;
	}
	public function getEntityidfield()
	{
		return $this->entityidfield;
	}
	public function getBasetable()
	{
		return $this->basetable;
	}
	public function getBasetableid()
	{
		return $this->basetableid;
	}
	public function getCustomtable()
	{
		return $this->customtable;
	}
	public function getGrouptable()
	{
		return $this->grouptable;
	}
	public function getType()
	{
		return $this->type;
	}
	public function getTableName()
	{
		return $this->tableName;
	}
}


