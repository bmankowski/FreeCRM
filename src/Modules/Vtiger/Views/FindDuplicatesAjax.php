<?php

namespace App\Modules\Vtiger\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class FindDuplicatesAjax  extends \App\Modules\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode) && method_exists($this, $mode)) {
			$this->$mode($request);
		}
	}
	/**
	 * Function to get listView count
	 * @param Vtiger_Request $request
	 */
	/* function getListViewCount(\App\Http\Vtiger_Request $request){
	  $moduleName = $request->getModule();
	  $cvId = $request->get('viewname');
	  if(empty($cvId)) {
	  $cvId = '0';
	  }

	  $searchKey = $request->get('search_key');
	  $searchValue = $request->get('search_value');

	  $listViewModel = \App\Modules\Vtiger\Models\ListView::getInstance($moduleName, $cvId);
	  $listViewModel->set('search_key', $searchKey);
	  $listViewModel->set('search_value', $searchValue);
	  $listViewModel->set('operator', $request->get('operator'));

	  $count = $listViewModel->getListViewCount();

	  return $count;
	  }



	  /**
	 * Function to get the page count for list
	 * @return total number of pages
	 */
	/* function getPageCount(\App\Http\Vtiger_Request $request){
	  $listViewCount = $this->getListViewCount($request);
	  $pagingModel = new \App\Modules\Vtiger\Models\Paging();
	  $pageLimit = $pagingModel->getPageLimit();
	  $pageCount = ceil((int) $listViewCount / (int) $pageLimit);

	  $result = [];
	  $result['page'] = $pageCount;
	  $response = new Vtiger_Response();
	  $response->setResult($result);
	  $response->emit();
	  } */
}
