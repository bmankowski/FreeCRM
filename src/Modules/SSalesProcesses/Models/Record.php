<?php

namespace App\Modules\SSalesProcesses\Models;

/**
 * Record Class for SSalesProcesses
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Record extends \App\Modules\Base\Models\Record
{

	/**
	 * Function returns the details of IStorages Hierarchy
	 * @param \App\Modules\Users\Models\Record $currentUser - User model (required)
	 * @return array
	 */
	public function getHierarchy($currentUser)
	{
		$focus = \App\Core\CRMEntity::getInstance($this->getModuleName());
		$hierarchy = $focus->getHierarchy($this->getId(), false, true, $currentUser);
		foreach ($hierarchy['entries'] as $storageId => $storageInfo) {
			preg_match('/<a href="+/', $storageInfo[0], $matches);
			if (!empty($matches)) {
				preg_match('/[.\s]+/', $storageInfo[0], $dashes);
				preg_match("/<a(.*)>(.*)<\/a>/i", $storageInfo[0], $name);

				$recordModel = \App\Modules\Base\Models\Record::getCleanInstance('SSalesProcesses');
				$recordModel->setId($storageId);
				$hierarchy['entries'][$storageId][0] = $dashes[0] . "<a href=" . $recordModel->getDetailViewUrl() . ">" . $name[2] . "</a>";
			}
		}
		return $hierarchy;
	}
}
