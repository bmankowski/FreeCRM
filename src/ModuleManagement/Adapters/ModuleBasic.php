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
 * ModuleBasic adapter class.
 * 
 * Base class for Module adapter with all public properties.
 * Maintains backward compatibility with vtlib\ModuleBasic.
 */
class ModuleBasic
{
	/** @var int|false ID of this instance */
	public $id = false;
	
	/** @var string|false Module name */
	public $name = false;
	
	/** @var string|false Module label */
	public $label = false;
	
	/** @var int Module version */
	public $version = 0;
	
	/** @var string|false Minimum version */
	public $minversion = false;
	
	/** @var string|false Maximum version */
	public $maxversion = false;
	
	/** @var int Presence (0 = enabled, 1 = disabled) */
	public $presence = 0;
	
	/** @var int Owned by (0 = Sharing Access Enabled, 1 = Sharing Access Disabled) */
	public $ownedby = 0;
	
	/** @var int|false Tab sequence */
	public $tabsequence = false;
	
	/** @var string|false Parent module */
	public $parent = false;
	
	/** @var int Customized flag (0 = standard, 1 = custom) */
	public $customized = 0;
	
	/** @var bool Is entity type (true = real module, false = extension) */
	public $isentitytype = true;
	
	/** @var string|false Entity ID column */
	public $entityidcolumn = false;
	
	/** @var string|false Entity ID field */
	public $entityidfield = false;
	
	/** @var string|false Base table name */
	public $basetable = false;
	
	/** @var string|false Base table ID column */
	public $basetableid = false;
	
	/** @var string|false Custom table name */
	public $customtable = false;
	
	/** @var string|false Group table name */
	public $grouptable = false;
	
	/** @var int Module type (0 = entity, 1 = inventory) */
	public $type = 0;
	
	/** @var string|null Table name */
	public $tableName;

	const EVENT_MODULE_ENABLED = 'module.enabled';
	const EVENT_MODULE_DISABLED = 'module.disabled';
	const EVENT_MODULE_POSTINSTALL = 'module.postinstall';
	const EVENT_MODULE_PREUNINSTALL = 'module.preuninstall';
	const EVENT_MODULE_PREUPDATE = 'module.preupdate';
	const EVENT_MODULE_POSTUPDATE = 'module.postupdate';
}



