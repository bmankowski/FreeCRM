<?php

namespace App\Modules\HelpDesk\Models;

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
	public const STATUS_FOR_APPROVAL = 'PLL_FOR_APPROVAL';
	public const STATUS_IN_PROGRESS = 'In Progress';
	public const STATUS_CLOSED = 'Closed';
	public const TERMINAL_STATUSES = ['Closed', 'Rejected'];

	public static function isDevActiveStatus(string $status): bool
	{
		$blockedStatuses = array_merge([self::STATUS_FOR_APPROVAL], self::TERMINAL_STATUSES);

		return !in_array($status, $blockedStatuses, true);
	}

	public static function canMarkDone(\App\Modules\Base\Models\Record $recordModel): bool
	{
		if (!$recordModel->isEditable()) {
			return false;
		}

		if (empty($recordModel->get('developer_id'))) {
			return false;
		}

		if (!self::isActorForOwnerField($recordModel, 'developer_id')) {
			return false;
		}

		$status = (string) $recordModel->get('ticketstatus');

		return self::isDevActiveStatus($status);
	}

	public static function canReportNotWorking(\App\Modules\Base\Models\Record $recordModel): bool
	{
		if (!$recordModel->isEditable()) {
			return false;
		}

		if (empty($recordModel->get('business_id'))) {
			return false;
		}

		if (!self::isActorForOwnerField($recordModel, 'business_id')) {
			return false;
		}

		return (string) $recordModel->get('ticketstatus') === self::STATUS_FOR_APPROVAL;
	}

	public static function canAccept(\App\Modules\Base\Models\Record $recordModel): bool
	{
		if (!$recordModel->isEditable()) {
			return false;
		}

		if (empty($recordModel->get('business_id'))) {
			return false;
		}

		if (!self::isActorForOwnerField($recordModel, 'business_id')) {
			return false;
		}

		return (string) $recordModel->get('ticketstatus') === self::STATUS_FOR_APPROVAL;
	}

	private static function isActorForOwnerField(\App\Modules\Base\Models\Record $recordModel, string $fieldName): bool
	{
		$ownerId = (int) $recordModel->get($fieldName);
		if ($ownerId <= 0) {
			return false;
		}

		$currentUserId = (int) (\App\User\CurrentUser::getId() ?? 0);
		if (\App\Fields\Owner::getType($ownerId) === 'Users') {
			return $currentUserId === $ownerId;
		}

		$privileges = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$userGroups = $privileges ? ($privileges->get('groups') ?? []) : [];

		return in_array($ownerId, $userGroups, true);
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
		$recordId = $recordModel->getId();

		if (self::canMarkDone($recordModel)) {
			$linkModelList['DETAILVIEWBASIC'][] = \App\Modules\Base\Models\Link::getInstanceFromValues([
				'linktype' => 'DETAILVIEWBASIC',
				'linklabel' => 'LBL_TICKET_MARK_DONE',
				'linkurl' => 'index.php?module=HelpDesk&view=TicketWorkflowModal&mode=done&record=' . $recordId,
				'linkclass' => 'btn-success',
				'showLabel' => 1,
				'modalView' => true,
			]);
		}

		if (self::canReportNotWorking($recordModel)) {
			$linkModelList['DETAILVIEWBASIC'][] = \App\Modules\Base\Models\Link::getInstanceFromValues([
				'linktype' => 'DETAILVIEWBASIC',
				'linklabel' => 'LBL_TICKET_STILL_NOT_WORKING',
				'linkurl' => 'index.php?module=HelpDesk&view=TicketWorkflowModal&mode=not_working&record=' . $recordId,
				'linkclass' => 'btn-warning',
				'showLabel' => 1,
				'modalView' => true,
			]);
		}

		if (self::canAccept($recordModel)) {
			$linkModelList['DETAILVIEWBASIC'][] = \App\Modules\Base\Models\Link::getInstanceFromValues([
				'linktype' => 'DETAILVIEWBASIC',
				'linklabel' => 'LBL_TICKET_ACCEPT',
				'linkurl' => 'index.php?module=HelpDesk&view=TicketWorkflowModal&mode=accept&record=' . $recordId,
				'linkclass' => 'btn-success',
				'showLabel' => 1,
				'modalView' => true,
			]);
		}

		$quotesModuleModel = \App\Modules\Base\Models\Module::getInstance('Faq');
		if ($quotesModuleModel->isPermitted('DetailView')) {
			$basicActionLink = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_CONVERT_FAQ',
				'linkurl' => $recordModel->getConvertFAQUrl(),
				'showLabel' => 1,
			);
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($basicActionLink);
		}

		return $linkModelList;
	}

	public function getDetailViewRelatedLinks()
	{
		$recordModel = $this->getRecord();
		$moduleName = $recordModel->getModuleName();

		$relatedLinks = parent::getDetailViewRelatedLinks();
		$parentModel = \App\Modules\Base\Models\Module::getInstance('OSSTimeControl');
		if ($parentModel->isActive()) {
			$relatedLinks[] = [
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'LBL_CHARTS',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showCharts&requestMode=charts',
				'linkicon' => '',
				'linkKey' => 'LBL_RECORD_SUMMARY',
				'related' => 'Charts'
			];
		}
		$showPSTab = (!\App\Core\AppConfig::module($moduleName, 'HIDE_SUMMARY_PRODUCTS_SERVICES')) && (\App\Utils\ModuleUtils::isModuleActive('Products') || \App\Utils\ModuleUtils::isModuleActive('Services') || \App\Utils\ModuleUtils::isModuleActive('Assets') || \App\Utils\ModuleUtils::isModuleActive('OSSSoldServices'));
		if ($showPSTab) {
			$relatedLinks[] = [
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'LBL_RECORD_SUMMARY_PRODUCTS_SERVICES',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showRelatedProductsServices&requestMode=summary',
				'linkicon' => '',
				'linkKey' => 'LBL_RECORD_SUMMARY',
				'related' => 'ProductsAndServices',
				'countRelated' => \App\Core\AppConfig::relation('SHOW_RECORDS_COUNT')
			];
		}
		return $relatedLinks;
	}
}
