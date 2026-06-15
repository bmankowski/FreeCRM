<?php

namespace App\Modules\Documents\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class DetailView extends \App\Modules\Base\Models\DetailView
{

	/**
	 * @return \App\Modules\Documents\Models\Record
	 */
	public function getRecord()
	{
		/** @var \App\Modules\Documents\Models\Record */
		return parent::getRecord();
	}

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param array $linkParams - parameters which will be used to calicaulate the params
	 * @return array - array of link models in the format as below
	 *                  array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams)
	{
		$linkModelList = parent::getDetailViewLinks($linkParams);
		$recordModel = $this->getRecord();

		if ($recordModel->get('active') && $recordModel->get('original_name') && $recordModel->get('location_type') === 'internal') {
			$basicActionLink = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_DOWNLOAD_FILE',
				'linkurl' => $recordModel->getDownloadFileURL(),
				'linkicon' => 'glyphicon glyphicon-download-alt'
			);
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($basicActionLink);
		}
		$basicActionLink = array(
			'linktype' => 'DETAILVIEW',
			'linklabel' => 'LBL_CHECK_FILE_INTEGRITY',
			'linkurl' => $recordModel->checkFileIntegrityURL(),
			'linkicon' => 'glyphicon glyphicon-saved'
		);
		$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($basicActionLink);

		if ($recordModel->get('active') && $recordModel->get('original_name') && $recordModel->get('location_type') === 'internal') {
			if (\App\Core\AppConfig::main('isActiveSendingMails') && \App\Modules\Mail\Models\Module::canUserSend((int) \App\User\CurrentUser::getId())) {
				$basicActionLink = array(
					'linktype' => 'DETAILVIEW',
					'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_EMAIL_FILE_AS_ATTACHMENT', 'Documents'),
					'linkhref' => true,
					'linktarget' => '_blank',
					'linkurl' => \App\Modules\Mail\Models\Module::getComposeUrl('Documents', (int) $recordModel->getId()),
					'linkicon' => 'glyphicon glyphicon-envelope'
				);
				$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($basicActionLink);
			}
		}

		return $linkModelList;
	}

	/**
	 * Function to get the detail view related links
	 * @return array - list of links parameters
	 */
	public function getDetailViewRelatedLinks()
	{
		$recordModel = $this->getRecord();
		$moduleName = $recordModel->getModuleName();
		$parentModuleModel = $this->getModule();
		$relatedLinks = parent::getDetailViewRelatedLinks();

		$relatedLinks[] = [
			'linktype' => 'DETAILVIEWTAB',
			'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_RELATIONS', $moduleName),
			'linkKey' => 'LBL_RECORD_SUMMARY',
			'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showDocumentRelations',
			'linkicon' => '',
			'related' => \App\Utils\Json::encode(\App\Modules\Documents\Models\Record::getReferenceModuleByDocId($recordModel->getId())),
			'countRelated' => \App\Core\AppConfig::relation('SHOW_RECORDS_COUNT')
		];
		return $relatedLinks;
	}
}
