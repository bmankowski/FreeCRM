<?php

namespace App\Modules\IStorages\Models;

/**
 * Record Class for IStorages
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Record extends \App\Modules\Base\Models\Record
{

	/**
	 * Function returns the details of IStorages Hierarchy
	 * @return <Array>
	 */
	public function getHierarchy()
	{
		$focus = \App\Core\CRMEntity::getInstance($this->getModuleName());
		$hierarchy = $focus->getHierarchy($this->getId());
		foreach ($hierarchy['entries'] as $storageId => $storageInfo) {
			preg_match('/<a href="+/', $storageInfo[0], $matches);
			if (!empty($matches)) {
				preg_match('/[.\s]+/', $storageInfo[0], $dashes);
				preg_match("/<a(.*)>(.*)<\/a>/i", $storageInfo[0], $name);

				$recordModel = \App\Modules\Base\Models\Record::getCleanInstance('IStorages');
				$recordModel->setId($storageId);
				$hierarchy['entries'][$storageId][0] = $dashes[0] . "<a href=" . $recordModel->getDetailViewUrl() . ">" . $name[2] . "</a>";
			}
		}
		return $hierarchy;
	}

	/**
	 * Function to retieve display value for a field
	 * @param string $fieldName - field name for which values need to get
	 * @return string
	 */
	public function getDisplayValue($fieldName, $recordId = false, $rawText = false)
	{
		// This is special field / displayed only in Products module [view=Detail relatedModule=IStorages]
		if ($fieldName == 'qtyinstock') {
			return $this->get($fieldName);
		}
		return parent::getDisplayValue($fieldName, $recordId, $rawText);
	}
}
