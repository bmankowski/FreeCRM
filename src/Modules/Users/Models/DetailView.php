<?php

namespace App\Modules\Users\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DetailView extends \App\Modules\Vtiger\Models\DetailView
{

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param <array> $linkParams - parameters which will be used to calicaulate the params
	 * @return <array> - array of link models in the format as below
	 *                   array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$recordModel = $this->getRecord();
		$recordId = $recordModel->getId();

		if (($currentUserModel->isAdminUser() === true || $currentUserModel->get('id') == $recordId) && $recordModel->get('status') == 'Active') {
			$recordModel = $this->getRecord();

			$detailViewLinks = array(
				array(
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => 'LBL_EDIT',
					'linkurl' => $recordModel->getEditViewUrl(),
					'linkicon' => ''
				),
			);
			if (vglobal('systemMode') != 'demo') {
				$detailViewLinks[] = array(
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => 'LBL_CHANGE_PASSWORD',
					'linkurl' => "javascript:Users_Detail_Js.triggerChangePassword('index.php?module=Users&view=EditAjax&mode=changePassword&record=$recordId','Users')",
					'linkicon' => ''
				);
			}
			foreach ($detailViewLinks as $detailViewLink) {
				$linkModelList['DETAILVIEWBASIC'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($detailViewLink);
			}
			$detailViewPreferenceLinks = array();
			if (vglobal('systemMode') != 'demo') {
				$detailViewPreferenceLinks[] = array(
					'linktype' => 'DETAILVIEWPREFERENCE',
					'linklabel' => 'LBL_CHANGE_PASSWORD',
					'linkurl' => "javascript:Users_Detail_Js.triggerChangePassword('index.php?module=Users&view=EditAjax&mode=changePassword&record=$recordId','Users')",
					'linkicon' => ''
				);
			}
			$detailViewPreferenceLinks[] = array(
				'linktype' => 'DETAILVIEWPREFERENCE',
				'linklabel' => 'LBL_EDIT',
				'linkurl' => $recordModel->getPreferenceEditViewUrl(),
				'linkicon' => ''
			);

			foreach ($detailViewPreferenceLinks as $detailViewLink) {
				$linkModelList['DETAILVIEWPREFERENCE'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($detailViewLink);
			}

			$detailViewActionLinks = [];
			if ($currentUserModel->isAdminUser() && $currentUserModel->get('id') != $recordId) {
				$detailViewActionLinks[] = [
						'linktype' => 'DETAILVIEW',
						'linklabel' => 'LBL_DELETE',
						'linkurl' => 'javascript:Users_Detail_Js.triggerDeleteUser("' . $recordModel->getDeleteUrl() . '")',
						'linkicon' => ''
				];
			}
			$detailViewActionLinks[] = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_CHANGE_ACCESS_KEY',
				'linkurl' => "javascript:Users_Detail_Js.triggerChangeAccessKey('index.php?module=Users&action=SaveAjax&mode=changeAccessKey&record=$recordId')",
				'linkicon' => ''
			);
			foreach ($detailViewActionLinks as $detailViewLink) {
				$linkModelList['DETAILVIEW'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($detailViewLink);
			}
			return $linkModelList;
		}
	}
}
