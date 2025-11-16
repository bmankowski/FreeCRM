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
 * FieldBasic adapter class.
 * 
 * Base class for Field adapter with all public properties.
 * Maintains backward compatibility with vtlib\FieldBasic.
 */
#[\AllowDynamicProperties]
class FieldBasic
{
	/** @var int ID of this field instance */
	public $id;
	
	/** @var string Field name */
	public $name;
	
	/** @var int|false Tab ID (module ID) */
	public $tabid = false;
	
	/** @var string|false Field label */
	public $label = false;
	
	/** @var string|false Table name */
	public $table = false;
	
	/** @var string|false Column name */
	public $column = false;
	
	/** @var string|false Column type */
	public $columntype = false;
	
	/** @var string Help info */
	public $helpinfo = '';
	
	/** @var int Summary field flag */
	public $summaryfield = 0;
	
	/** @var string|false Header field */
	public $header_field = false;
	
	/** @var int Max length text */
	public $maxlengthtext = 0;
	
	/** @var int Max width column */
	public $maxwidthcolumn = 0;
	
	/** @var int Mass editable flag */
	public $masseditable = 1;
	
	/** @var int UI type */
	public $uitype = 1;
	
	/** @var string Type of data */
	public $typeofdata = 'V~O';
	
	/** @var int Display type */
	public $displaytype = 1;
	
	/** @var int Generated type */
	public $generatedtype = 1;
	
	/** @var int Readonly flag */
	public $readonly = 1;
	
	/** @var int Presence (0 = enabled, 1 = disabled) */
	public $presence = 2;
	
	/** @var string Default value */
	public $defaultvalue = '';
	
	/** @var int Maximum length */
	public $maximumlength = 100;
	
	/** @var int|false Sequence */
	public $sequence = false;
	
	/** @var int Quick create flag */
	public $quickcreate = 1;
	
	/** @var int|false Quick create sequence */
	public $quicksequence = false;
	
	/** @var string Info type */
	public $info_type = 'BAS';
	
	/** @var mixed Block instance or ID */
	public $block;
	
	/** @var string Field parameters */
	public $fieldparams = '';
}



