<?php

namespace App\Modules\Settings\CronTasks\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


class List extends \App\Modules\Settings\Vtiger\Views\List
{

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, CRM_Viewer $viewer)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$listViewModel = \App\Modules\Settings\Vtiger\Models\ListView::getInstance($qualifiedModuleName);
		$listViewModel->set('orderby', 'sequence');

		$pagingModel = new \App\Modules\Vtiger\Models\Paging();

		if (!$this->listViewHeaders) {
			$this->listViewHeaders = $listViewModel->getListViewHeaders();
		}
		if (!$this->listViewEntries) {
			$this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
		}

		$viewer->assign('MODULE_MODEL', $listViewModel->getModule());
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
		$viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
	}
}
