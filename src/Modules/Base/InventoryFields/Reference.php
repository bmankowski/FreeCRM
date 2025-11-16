<?php

namespace App\Modules\Base\InventoryFields;

/**
 * Inventory Reference Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Reference extends Basic
{

	protected $name = 'Reference';
	protected $defaultLabel = 'LBL_REFERENCE';
	protected $columnName = 'ref';
	protected $dbType = 'int';
	protected $params = ['modules'];

	/**
	 * Getting value to display
	 * @param type $value
	 * @return type
	 */
	public function getDisplayValue($value)
	{
		if ($value == 0) {
			return '';
		}
		$metaData = \vtlib\Functions::getCRMRecordMetadata($value);
		$linkValue = '<a class="moduleColor_' . $metaData['setype'] . '" href="index.php?module=' . $metaData['setype'] . '&view=Detail&record=' . $value . '" title="' . \App\Runtime\Vtiger_Language_Handler::translate($metaData['setype'], $metaData['setype']) . '">' . \App\Record::getLabel($value) . '</a>';
		return $linkValue;
	}

	/**
	 * Getting value to display
	 * @param type $value
	 * @return string
	 */
	public function getEditValue($value)
	{
		if (empty($value)) {
			return '';
		}
		$value = \App\Record::getLabel($value);
		return $value;
	}

	public function getReferenceModules()
	{
		$params = \App\Utils\Json::decode($this->get('params'));
		return $params['modules'];
	}

	public function getReferenceModule($record)
	{
		if (!empty($record)) {
			$metadata = \vtlib\Functions::getCRMRecordMetadata($record);
			return $metadata['setype'];
		}
		return '';
	}
}
