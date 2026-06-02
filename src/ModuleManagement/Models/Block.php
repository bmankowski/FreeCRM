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
 * Block value object.
 * 
 * Immutable representation of a block with all properties from Block class.
 */
class Block
{
	/** @var int Block ID */
	private $id;
	
	/** @var string Block label */
	private $label;
	
	/** @var int Sequence */
	private $sequence;
	
	/** @var int Show title flag */
	private $showtitle;
	
	/** @var int Visible flag */
	private $visible;
	
	/** @var int In create view flag */
	private $increateview;
	
	/** @var int In edit view flag */
	private $ineditview;
	
	/** @var int In detail view flag */
	private $indetailview;
	
	/** @var int Display status */
	private $display_status;
	
	/** @var int Is custom flag */
	private $iscustom;
	
	/** @var mixed Module instance or ID */
	private $module;

	/**
	 * Constructor.
	 * 	 * @param int $id
	 * @param string $label
	 * @param int $sequence
	 * @param int $showtitle
	 * @param int $visible
	 * @param int $increateview
	 * @param int $ineditview
	 * @param int $indetailview
	 * @param int $display_status
	 * @param int $iscustom
	 * @param mixed $module
	 */
	public function __construct(
		$id = null,
		$label = null,
		$sequence = null,
		$showtitle = 0,
		$visible = 0,
		$increateview = 0,
		$ineditview = 0,
		$indetailview = 0,
		$display_status = 1,
		$iscustom = 0,
		$module = null
	) {
		$this->id = $id;
		$this->label = $label;
		$this->sequence = $sequence;
		$this->showtitle = $showtitle;
		$this->visible = $visible;
		$this->increateview = $increateview;
		$this->ineditview = $ineditview;
		$this->indetailview = $indetailview;
		$this->display_status = $display_status;
		$this->iscustom = $iscustom;
		$this->module = $module;
	}

	public function getId() { return $this->id; }
	public function getLabel() { return $this->label; }
	public function getSequence() { return $this->sequence; }
	public function getShowtitle() { return $this->showtitle; }
	public function getVisible() { return $this->visible; }
	public function getIncreateview() { return $this->increateview; }
	public function getIneditview() { return $this->ineditview; }
	public function getIndetailview() { return $this->indetailview; }
	public function getDisplay_status() { return $this->display_status; }
	public function getIscustom() { return $this->iscustom; }
	public function getModule() { return $this->module; }
}





