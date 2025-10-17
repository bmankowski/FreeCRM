<?php

namespace App\Modules\KnowledgeBase\Models;

/**
 * Detail View Model for KnowledgeBase
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Krzysztof Gastołek <krzysztof.gastolek@wars.pl>
 */
class DetailView extends \App\Modules\Vtiger\Models\DetailView
{

	public function getDetailViewLinks($linkParams)
	{
		$recordModel = $this->getRecord();
		$recordId = $recordModel->get('id');
		$moduleName = $recordModel->getModuleName();
		$relatedLinkEntries = [
			[
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_PREVIEW', $moduleName),
				'linkKey' => 'LBL_RECORD_PREVIEW',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showPreview',
				'linkicon' => '',
				'related' => 'Summary'
			],
			[
				'linktype' => 'DETAILVIEWBASIC',
				'linkurl' => 'javascript:KnowledgeBase_Popup_Js.getInstance().showPresentationContent(' . $recordId . ');',
				'linkicon' => 'glyphicon glyphicon-resize-full',
				'title' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_FULL_SCREEN', $moduleName),
				'linkhint' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_FULL_SCREEN', $moduleName)
			]
		];
		$relatedLinks = [];
		foreach ($relatedLinkEntries as $relatedLinkEntry) {
			$relatedLinks[] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($relatedLinkEntry);
		}
		$linkModelList = parent::getDetailViewLinks($linkParams);
		foreach ($relatedLinks as $relatedLink) {
			$linkModelList[$relatedLink->getType()][] = $relatedLink;
		}
		return $linkModelList;
	}
}
