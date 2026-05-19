<?php

namespace App\Modules\Kandydaci\Models;

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
class DetailView extends \App\Modules\Base\Models\DetailView
{
	/** {@inheritdoc} */
	public function getDetailViewLinks($linkParams)
	{
		$relatedLinks = parent::getDetailViewLinks($linkParams);
		$recordModel = $this->getRecord();
		$moduleModel = $recordModel->getModule();
		$recordId = (int) $recordModel->getId();
		if ($moduleModel->isPermitted('MassComposeEmail') && \App\Core\AppConfig::main('isActiveSendingMails') && \App\Email\Mail::getDefaultSmtp()) {
			$relatedLinks['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues([
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_SEND_EMAIL',
				'linkurl' => '#',
				'linkhref' => true,
				'linkicon' => 'glyphicon glyphicon-envelope',
				'linkclass' => 'js-send-email-modal',
				'title' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEND_EMAIL'),
				'linkdata' => [
					'record-id' => $recordId,
					'module-name' => 'Kandydaci',
				],
			]);
		}
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if ($userPrivilegesModel->hasModulePermission('Konsultanci')) {
			$relatedLinks['DETAIL_VIEW_ADDITIONAL'][] =  \App\Modules\Base\Models\Link::getInstanceFromValues([
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
			array_unshift($relatedLinks['DETAIL_VIEW_ADDITIONAL'],\App\Modules\Base\Models\Link::getInstanceFromValues([
				'linktype' => 'DETAIL_VIEW_ADDITIONAL',
				'linkdata' => ['candidate-id' => $recordModel->getId()],
				'linkclass' => 'btn btn-sm btn-info js-show-modal toggle-star-candidate',
				'linkicon' => $linkiconModifier.'fa-star toggle-star-candidate-span',
			]));

		}
		return $relatedLinks;
	}
}
