<?php

namespace App\Modules\Settings\MappedFields\Models;



/**
 * Record Class for MappedFields Settings
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class Record extends \App\Modules\Settings\Base\Models\Record
{

	public function getId()
	{
		return $this->get('id');
	}

	public function getName()
	{
		return \App\Utils\ModuleUtils::getModuleName($this->get('tabid'));
	}

	public function getEditViewUrl()
	{
		return 'index.php?module=MappedFields&parent=Settings&view=Edit&record=' . $this->getId();
	}

	public function getModule()
	{
		return $this->module;
	}

	public function setModule($moduleName)
	{
		$this->module = \App\Modules\Base\Models\Module::getInstance($moduleName);
		return $this;
	}

	/**
	 * Function to get the list view actions for the record
	 * @return <Array> - Associate array of \App\Modules\Base\Models\Link instances
	 */
	public function getRecordLinks()
	{

		$links = [];

		$recordLinks = [
				[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT_RECORD',
				'linkurl' => $this->getEditViewUrl(),
				'linkicon' => 'glyphicon glyphicon-pencil'
			],
				[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EXPORT_RECORD',
				'linkurl' => 'index.php?module=MappedFields&parent=Settings&action=ExportTemplate&id=' . $this->getId(),
				'linkicon' => 'glyphicon glyphicon-export'
			],
				[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => 'javascript:void(0);',
				'linkicon' => 'glyphicon glyphicon-trash',
				'linkclass' => 'btn btn-xs btn-danger deleteMap',
			]
		];
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}

		return $links;
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $key
	 * @return string
	 */
	public function getDisplayValue(string $key): string
	{
		$value = $this->get($key);
		switch ($key) {
			case 'status':
				$value = $value ? 'active' : 'inactive';
				break;
		}
		return $value;
	}
}
