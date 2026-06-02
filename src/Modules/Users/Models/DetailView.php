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

class DetailView extends \App\Modules\Base\Models\DetailView
{

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param array $linkParams - parameters which will be used to calicaulate the params
	 * @return array - array of link models in the format as below
	 *                  array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams)
	{
		$currentUserModel = \App\User\CurrentUser::get();
		$recordModel = $this->getRecord();
		$recordId = $recordModel->getId();

		if (!(($currentUserModel->isAdminUser() === true || $currentUserModel->get('id') == $recordId) && $recordModel->get('status') == 'Active')) {
			return [];
		}

		$isPreference = ($linkParams['VIEW'] ?? '') === 'PreferenceDetail';
		$showPassword = \App\Core\AppConfig::main('systemMode') != 'demo';
		$changePasswordUrl = "javascript:Users_Detail_Js.triggerChangePassword('index.php?module=Users&view=EditAjax&mode=changePassword&record=$recordId','Users')";
		$linkModelList = [];

		if ($isPreference) {
			$basicLinks = [
				[
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => 'LBL_EDIT',
					'linkurl' => $recordModel->getPreferenceEditViewUrl(),
					'linkicon' => 'glyphicon glyphicon-pencil',
					'showLabel' => 1,
				],
			];
			if ($showPassword) {
				$basicLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => 'LBL_CHANGE_PASSWORD',
					'linkurl' => $changePasswordUrl,
					'linkicon' => 'glyphicon glyphicon-lock',
					'showLabel' => 1,
				];
			}
			foreach ($basicLinks as $detailViewLink) {
				$linkModelList['DETAILVIEWBASIC'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($detailViewLink);
			}

			$preferenceLinks = $basicLinks;
			foreach ($preferenceLinks as $detailViewLink) {
				$detailViewLink['linktype'] = 'DETAILVIEWPREFERENCE';
				$linkModelList['DETAILVIEWPREFERENCE'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($detailViewLink);
			}

			$detailViewActionLinks = [
				[
					'linktype' => 'DETAILVIEW',
					'linklabel' => 'LBL_CHANGE_ACCESS_KEY',
					'linkurl' => "javascript:Users_Detail_Js.triggerChangeAccessKey('index.php?module=Users&action=SaveAjax&mode=changeAccessKey&record=$recordId')",
					'linkicon' => 'glyphicon glyphicon-key',
					'showLabel' => 1,
				],
			];
		} else {
			$detailViewLinks = [
				[
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => 'LBL_EDIT',
					'linkurl' => $recordModel->getEditViewUrl(),
					'linkicon' => 'glyphicon glyphicon-pencil',
					'showLabel' => 1,
				],
			];
			if ($showPassword) {
				$detailViewLinks[] = [
					'linktype' => 'DETAILVIEWBASIC',
					'linklabel' => 'LBL_CHANGE_PASSWORD',
					'linkurl' => $changePasswordUrl,
					'linkicon' => 'glyphicon glyphicon-lock',
					'showLabel' => 1,
				];
			}
			foreach ($detailViewLinks as $detailViewLink) {
				$linkModelList['DETAILVIEWBASIC'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($detailViewLink);
			}

			$detailViewPreferenceLinks = [];
			if ($showPassword) {
				$detailViewPreferenceLinks[] = [
					'linktype' => 'DETAILVIEWPREFERENCE',
					'linklabel' => 'LBL_CHANGE_PASSWORD',
					'linkurl' => $changePasswordUrl,
					'linkicon' => 'glyphicon glyphicon-lock',
					'showLabel' => 1,
				];
			}
			$detailViewPreferenceLinks[] = [
				'linktype' => 'DETAILVIEWPREFERENCE',
				'linklabel' => 'LBL_EDIT',
				'linkurl' => $recordModel->getPreferenceEditViewUrl(),
				'linkicon' => 'glyphicon glyphicon-pencil',
				'showLabel' => 1,
			];
			foreach ($detailViewPreferenceLinks as $detailViewLink) {
				$linkModelList['DETAILVIEWPREFERENCE'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($detailViewLink);
			}

			$detailViewActionLinks = [];
			if ($currentUserModel->isAdminUser() && $currentUserModel->get('id') != $recordId) {
				$detailViewActionLinks[] = [
					'linktype' => 'DETAILVIEW',
					'linklabel' => 'LBL_DELETE',
					'linkurl' => 'javascript:Users_Detail_Js.triggerDeleteUser("' . $recordModel->getDeleteUrl() . '")',
					'linkicon' => 'glyphicon glyphicon-trash',
					'showLabel' => 1,
				];
			}
			$detailViewActionLinks[] = [
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_CHANGE_ACCESS_KEY',
				'linkurl' => "javascript:Users_Detail_Js.triggerChangeAccessKey('index.php?module=Users&action=SaveAjax&mode=changeAccessKey&record=$recordId')",
				'linkicon' => 'glyphicon glyphicon-key',
				'showLabel' => 1,
			];
		}

		foreach ($detailViewActionLinks as $detailViewLink) {
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($detailViewLink);
		}

		return $linkModelList;
	}
}
