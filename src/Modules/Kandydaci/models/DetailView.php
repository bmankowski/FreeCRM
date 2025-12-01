<?php

/**
 * Konsultanci detail view model file.
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author Arkadiusz Sołek <a.solek@yetiforce.com>
 */
/**
 * Konsultanci detail view model class.
 */
class Kandydaci_DetailView_Model extends Vtiger_DetailView_Model
{
	/** {@inheritdoc} */
	public function getDetailViewLinks(array $linkParams): array
	{
		$relatedLinks = parent::getDetailViewLinks($linkParams);
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$recordModel = $this->getRecord();
		if ($userPrivilegesModel->hasModulePermission('Konsultanci')) {
			$relatedLinks['DETAIL_VIEW_ADDITIONAL'][] =  Vtiger_Link_Model::getInstanceFromValues([
				'linktype' => 'DETAIL_VIEW_ADDITIONAL',
				'linkdata' => ['url' => 'index.php?module=' . $recordModel->getModuleName() . '&view=TransformCandidateToConsultantModal&record=' . $recordModel->getId()],
				'linkclass' => 'btn btn-sm btn-info js-show-modal',
				'linkicon' => 'fas fa-user-tie'
			]);
			//Push as first element
			if($recordModel->get("starred")){
				$linkiconModifier = 'fas ';
			}else{
				$linkiconModifier = 'far ';
			}
			array_unshift($relatedLinks['DETAIL_VIEW_ADDITIONAL'],Vtiger_Link_Model::getInstanceFromValues([
				'linktype' => 'DETAIL_VIEW_ADDITIONAL',
				'linkdata' => ['candidate-id' => $recordModel->getId()],
				'linkclass' => 'btn btn-sm btn-info js-show-modal toggle-star-candidate',
				'linkicon' => $linkiconModifier.'fa-star toggle-star-candidate-span',
			]));

		}
		return $relatedLinks;
	}
}
