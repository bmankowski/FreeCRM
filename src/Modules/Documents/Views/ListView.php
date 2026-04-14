<?php

namespace App\Modules\Documents\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


class ListView extends \App\Modules\Base\Views\ListView
{

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		parent::initializeListViewContents($request, $viewer);
		
		// Prepare Documents-specific data for ListViewLeftSide template
		$this->prepareDocumentsListViewData($viewer, $request);
	}

	/**
	 * Prepare data for Documents ListViewLeftSide template
	 * Moves function calls from templates to controller for better MVC separation
	 */
	protected function prepareDocumentsListViewData($viewer, $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		
		// Prepare global config and permissions
		$showTimelineInListView = \App\Core\AppConfig::module('ModTracker', 'SHOW_TIMELINE_IN_LISTVIEW');
		$canShowTimeline = in_array($moduleName, $showTimelineInListView ? $showTimelineInListView : []) && $moduleModel->isPermitted('TimeLineList');
		$modTrackerUnreviewedCount = \App\Core\AppConfig::module('ModTracker', 'UNREVIEWED_COUNT');
		$canReviewUpdates = $moduleModel->isPermitted('ReviewingUpdates');
		$isTrackingEnabled = $moduleModel->isTrackingEnabled();
		
		$viewer->assign('SHOW_TIMELINE_IN_LISTVIEW', $canShowTimeline);
		$viewer->assign('SHOW_MODTRACKER_UNREVIEWED', $modTrackerUnreviewedCount && $canReviewUpdates && $isTrackingEnabled);
		
		// Per-record left-column icon classes (viewer: RECORD_LEFT_ICON_CLASSES)
		$listViewEntries = $this->listViewEntries;
		$imageClasses = [];
		if ($listViewEntries) {
			foreach ($listViewEntries as $record) {
				$recordId = $record->getId();
				$imageClasses[$recordId] = \App\Modules\Documents\Models\Record::getFileIconByFileType($record->get('filetype'));
			}
		}
		$viewer->assign('RECORD_LEFT_ICON_CLASSES', $imageClasses);
	}

}

