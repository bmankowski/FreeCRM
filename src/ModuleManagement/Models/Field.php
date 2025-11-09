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
 * Field value object.
 * 
 * Immutable representation of a field with all properties from FieldBasic.
 */
class Field
{
	/** @var int Field ID */
	private $id;
	
	/** @var string Field name */
	private $name;
	
	/** @var int|false Tab ID (module ID) */
	private $tabid;
	
	/** @var string|false Field label */
	private $label;
	
	/** @var string|false Table name */
	private $table;
	
	/** @var string|false Column name */
	private $column;
	
	/** @var string|false Column type */
	private $columntype;
	
	/** @var string Help info */
	private $helpinfo;
	
	/** @var int Summary field flag */
	private $summaryfield;
	
	/** @var string|false Header field */
	private $header_field;
	
	/** @var int Max length text */
	private $maxlengthtext;
	
	/** @var int Max width column */
	private $maxwidthcolumn;
	
	/** @var int Mass editable flag */
	private $masseditable;
	
	/** @var int UI type */
	private $uitype;
	
	/** @var string Type of data */
	private $typeofdata;
	
	/** @var int Display type */
	private $displaytype;
	
	/** @var int Generated type */
	private $generatedtype;
	
	/** @var int Readonly flag */
	private $readonly;
	
	/** @var int Presence (0 = enabled, 1 = disabled) */
	private $presence;
	
	/** @var string Default value */
	private $defaultvalue;
	
	/** @var int Maximum length */
	private $maximumlength;
	
	/** @var int|false Sequence */
	private $sequence;
	
	/** @var int Quick create flag */
	private $quickcreate;
	
	/** @var int|false Quick create sequence */
	private $quicksequence;
	
	/** @var string Info type */
	private $info_type;
	
	/** @var mixed Block instance or ID */
	private $block;
	
	/** @var string Field parameters */
	private $fieldparams;

	/**
	 * Constructor.
	 * 
	 * @param int $id
	 * @param string $name
	 * @param int|false $tabid
	 * @param string|false $label
	 * @param string|false $table
	 * @param string|false $column
	 * @param string|false $columntype
	 * @param string $helpinfo
	 * @param int $summaryfield
	 * @param string|false $header_field
	 * @param int $maxlengthtext
	 * @param int $maxwidthcolumn
	 * @param int $masseditable
	 * @param int $uitype
	 * @param string $typeofdata
	 * @param int $displaytype
	 * @param int $generatedtype
	 * @param int $readonly
	 * @param int $presence
	 * @param string $defaultvalue
	 * @param int $maximumlength
	 * @param int|false $sequence
	 * @param int $quickcreate
	 * @param int|false $quicksequence
	 * @param string $info_type
	 * @param mixed $block
	 * @param string $fieldparams
	 */
	public function __construct(
		$id = null,
		$name = null,
		$tabid = false,
		$label = false,
		$table = false,
		$column = false,
		$columntype = false,
		$helpinfo = '',
		$summaryfield = 0,
		$header_field = false,
		$maxlengthtext = 0,
		$maxwidthcolumn = 0,
		$masseditable = 1,
		$uitype = 1,
		$typeofdata = 'V~O',
		$displaytype = 1,
		$generatedtype = 1,
		$readonly = 1,
		$presence = 2,
		$defaultvalue = '',
		$maximumlength = 100,
		$sequence = false,
		$quickcreate = 1,
		$quicksequence = false,
		$info_type = 'BAS',
		$block = null,
		$fieldparams = ''
	) {
		$this->id = $id;
		$this->name = $name;
		$this->tabid = $tabid;
		$this->label = $label;
		$this->table = $table;
		$this->column = $column;
		$this->columntype = $columntype;
		$this->helpinfo = $helpinfo;
		$this->summaryfield = $summaryfield;
		$this->header_field = $header_field;
		$this->maxlengthtext = $maxlengthtext;
		$this->maxwidthcolumn = $maxwidthcolumn;
		$this->masseditable = $masseditable;
		$this->uitype = $uitype;
		$this->typeofdata = $typeofdata;
		$this->displaytype = $displaytype;
		$this->generatedtype = $generatedtype;
		$this->readonly = $readonly;
		$this->presence = $presence;
		$this->defaultvalue = $defaultvalue;
		$this->maximumlength = $maximumlength;
		$this->sequence = $sequence;
		$this->quickcreate = $quickcreate;
		$this->quicksequence = $quicksequence;
		$this->info_type = $info_type;
		$this->block = $block;
		$this->fieldparams = $fieldparams;
	}

	public function getId() { return $this->id; }
	public function getName() { return $this->name; }
	public function getTabid() { return $this->tabid; }
	public function getModuleId() { return $this->tabid; }
	public function getLabel() { return $this->label; }
	public function getTable() { return $this->table; }
	public function getColumn() { return $this->column; }
	public function getColumntype() { return $this->columntype; }
	public function getHelpinfo() { return $this->helpinfo; }
	public function getSummaryfield() { return $this->summaryfield; }
	public function getHeader_field() { return $this->header_field; }
	public function getMaxlengthtext() { return $this->maxlengthtext; }
	public function getMaxwidthcolumn() { return $this->maxwidthcolumn; }
	public function getMasseditable() { return $this->masseditable; }
	public function getUitype() { return $this->uitype; }
	public function getTypeofdata() { return $this->typeofdata; }
	public function getDisplaytype() { return $this->displaytype; }
	public function getGeneratedtype() { return $this->generatedtype; }
	public function getReadonly() { return $this->readonly; }
	public function getPresence() { return $this->presence; }
	public function getDefaultvalue() { return $this->defaultvalue; }
	public function getMaximumlength() { return $this->maximumlength; }
	public function getSequence() { return $this->sequence; }
	public function getQuickcreate() { return $this->quickcreate; }
	public function getQuicksequence() { return $this->quicksequence; }
	public function getInfo_type() { return $this->info_type; }
	public function getBlock() { return $this->block; }
	public function getFieldparams() { return $this->fieldparams; }
}

