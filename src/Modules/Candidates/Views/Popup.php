<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Candidates\Views;

use App\Http\Vtiger_Request;
use App\Modules\Candidates\Services\CvSkillsSearch;
use App\Runtime\CRM_Viewer;

class Popup extends \App\Modules\Base\Views\Popup
{
	public function initializeListViewContents(Vtiger_Request $request, CRM_Viewer $viewer): void
	{
		CvSkillsSearch::applyToRequest($request);
		parent::initializeListViewContents($request, $viewer);
	}

	public function getListViewCount(Vtiger_Request $request)
	{
		CvSkillsSearch::applyToRequest($request);

		$moduleName = $this->getModule($request);
		$sourceModule = $request->get('src_module');
		$sourceField = $request->get('src_field');
		$sourceRecord = $request->get('src_record');
		$orderBy = $request->get('orderby');
		$sortOrder = $request->get('sortorder');
		$currencyId = $request->get('currency_id');
		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$relatedParentModule = $request->get('related_parent_module');
		$relatedParentId = $request->get('related_parent_id');

		if (!empty($relatedParentModule) && !empty($relatedParentId)) {
			$parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($relatedParentId, $relatedParentModule);
			$listViewModel = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $moduleName, $label);
		} else {
			$listViewModel = \App\Modules\Base\Models\ListView::getInstanceForPopup($moduleName, $sourceModule);
		}

		if (!empty($sourceModule)) {
			$listViewModel->set('src_module', $sourceModule);
			$listViewModel->set('src_field', $sourceField);
			$listViewModel->set('src_record', $sourceRecord);
			$listViewModel->set('currency_id', $currencyId);
		}

		if (!empty($orderBy)) {
			$listViewModel->set('orderby', $orderBy);
			$listViewModel->set('sortorder', $sortOrder);
		}
		if ((!empty($searchKey)) && (!empty($searchValue))) {
			$listViewModel->set('search_key', $searchKey);
			$listViewModel->set('search_value', $searchValue);
		}

		$searchParams = $request->get('search_params');
		if (!\is_array($searchParams)) {
			$searchParams = [];
		}
		$transformedSearchParams = $listViewModel->getQueryGenerator()->parseBaseSearchParamsToCondition($searchParams);
		$listViewModel->set('search_params', $transformedSearchParams);

		if (!empty($relatedParentModule) && !empty($relatedParentId)) {
			return $listViewModel->getRelatedEntriesCount();
		}

		return $listViewModel->getListViewCount();
	}
}
