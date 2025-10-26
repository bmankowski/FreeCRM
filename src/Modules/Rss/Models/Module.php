<?php

namespace App\Modules\Rss\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Module extends \App\Modules\Base\Models\Module
{

	/**
	 * Function to get the Quick Links for the module
	 * @param <Array> $linkParams
	 * @return <Array> List of \App\Modules\Base\Models\Link instances
	 */
	public function getSideBarLinks($linkParams)
	{
		$linkTypes = array('SIDEBARLINK', 'SIDEBARWIDGET');
		$links = \App\Modules\Base\Models\Link::getAllByType($this->getId(), $linkTypes, $linkParams);

		$quickLinks = array(
			array(
				'linktype' => 'SIDEBARLINK',
				'linklabel' => 'LBL_ADD_FEED_SOURCE',
				'linkurl' => $this->getDefaultUrl(),
				'linkicon' => '',
			)
		);
		foreach ($quickLinks as $quickLink) {
			$links['SIDEBARLINK'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($quickLink);
		}
		$quickWidgets = array(
			array(
				'linktype' => 'SIDEBARWIDGET',
				'linklabel' => 'LBL_RSS_FEED_SOURCES',
				'linkurl' => 'module=' . $this->get('name') . '&view=ViewTypes&mode=getRssWidget',
				'linkicon' => ''
			),
		);
		foreach ($quickWidgets as $quickWidget) {
			$links['SIDEBARWIDGET'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($quickWidget);
		}

		return $links;
	}

	/**
	 * Function to get rss sources list
	 */
	public function getRssSources()
	{
		$db = \App\Database\PearDatabase::getInstance();

		$sql = 'Select *from vtiger_rss';
		$result = $db->pquery($sql, array());
		$noOfRows = $db->num_rows($result);

		$records = array();
		for ($i = 0; $i < $noOfRows; ++$i) {
			$row = $db->query_result_rowdata($result, $i);
			$row['id'] = $row['rssid'];
			$records[$row['id']] = $this->getRecordFromArray($row);
		}
		return $records;
	}
}
