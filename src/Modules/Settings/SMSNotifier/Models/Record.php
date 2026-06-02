<?php

namespace App\Modules\Settings\SMSNotifier\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Record extends \App\Modules\Settings\Base\Models\Record
{

	protected $module;
	
	/**
	 * Function to get Id of this record instance
	 * @return int Id
	 */
	public function getId()
	{
		return $this->get('id');
	}

	/**
	 * Function to get Name of this record instance
	 * @return string Name
	 */
	public function getName()
	{
		return '';
	}

	/**
	 * Function to get module of this record instance
	 * @return \App\Modules\Settings\SMSNotifier\Models\Module $moduleModel
	 */
	public function getModule()
	{
		return $this->module;
	}

	/**
	 * Function to set module instance to this record instance
	 * @param \App\Modules\Settings\SMSNotifier\Models\Module $moduleModel
	 * @return \App\Modules\Settings\SMSNotifier\Models\Record this record
	 */
	public function setModule($moduleModel)
	{
		$this->module = $moduleModel;
		return $this;
	}

	/**
	 * Function to get Edit view url
	 * @return string Url
	 */
	public function getEditViewUrl()
	{
		$moduleModel = $this->getModule();
		return 'index.php?module=' . $moduleModel->getName() . '&parent=' . $moduleModel->getParentName() . '&view=Edit&record=' . $this->getId();
	}

	/**
	 * Function to get Delete url
	 * @return string Url
	 */
	public function getDeleteUrl()
	{
		$moduleModel = $this->getModule();
		return 'index.php?module=' . $moduleModel->getName() . '&parent=' . $moduleModel->getParentName() . '&action=Delete&record=' . $this->getId();
	}

	/**
	 * Function to get record links
	 * @return array list of link models <\App\Modules\Base\Models\Link>
	 */
	public function getRecordLinks()
	{
		$links = array();
		$recordLinks = array(
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT',
				'linkurl' => "javascript:Settings_SMSNotifier_ListView_Js.triggerEdit(event, '" . $this->getEditViewUrl() . "');",
				'linkicon' => 'glyphicon glyphicon-pencil'
			),
			array(
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DELETE',
				'linkurl' => "javascript:Settings_SMSNotifier_ListView_Js.triggerDelete(event, '" . $this->getDeleteUrl() . "');",
				'linkicon' => 'glyphicon glyphicon-trash'
			)
		);
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}

		return $links;
	}

	/**
	 * Function to getDisplay value of every field
	 * @param string field name
	 * @return string field value
	 */
	public function getDisplayValue(string $key): string
	{
		$value = $this->get($key);
		if ($key === 'isactive') {
			if ($value) {
				$value = 'Yes';
			} else {
				$value = 'No';
			}
		}
		return $value;
	}

	/**
	 * Function to get Editable fields for this instance
	 * @return array field models list <\App\Modules\Settings\SMSNotifier\Models\Field>
	 */
	public function getEditableFields()
	{
		$editableFieldsList = $this->getModule()->getEditableFields();
		return $editableFieldsList;
	}

	/**
	 * Function to save the record
	 */
	public function save($request = null)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$params = array($this->get('providertype'), $this->get('isactive'), $this->get('username'), $this->get('password'), $this->get('parameters'));
		$id = $this->getId();

		if ($id) {
			$query = 'UPDATE vtiger_smsnotifier_servers SET providertype = ?, isactive = ?, username = ?, password = ?, parameters = ? WHERE id = ?';
			array_push($params, $id);
		} else {
			$query = 'INSERT INTO vtiger_smsnotifier_servers(providertype, isactive, username, password, parameters) VALUES(?, ?, ?, ?, ?)';
		}
		$db->pquery($query, $params);
	}

	/**
	 * Function to get record instance by using id and moduleName
	 * @param int $recordId
	 * @param string $qualifiedModuleName
	 * @return ?\App\Modules\Settings\SMSNotifier\Models\Record RecordModel
	 */
	static public function getInstanceById($recordId, $qualifiedModuleName)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM vtiger_smsnotifier_servers WHERE id = ?', array($recordId));

		if ($db->num_rows($result)) {
			$moduleModel = \App\Modules\Settings\Base\Models\Module::getInstance($qualifiedModuleName);
			$rowData = $db->query_result_rowdata($result, 0);

			$recordModel = new self();
			$recordModel->setData($rowData);
			$recordModel->setModule($moduleModel);

			$parameters = \App\Utils\Json::decode(\App\Utils\ListViewUtils::decodeHtml($recordModel->get('parameters')));
			foreach ($parameters as $fieldName => $fieldValue) {
				$recordModel->set($fieldName, $fieldValue);
			}

			return $recordModel;
		}
		return null;
	}

	/**
	 * Function to get clean record instance by using moduleName
	 * @param string $qualifiedModuleName
	 * @return \App\Modules\Settings\SMSNotifier\Models\Record
	 */
	static public function getCleanInstance($qualifiedModuleName)
	{
		$recordModel = new self();
		$moduleModel = \App\Modules\Settings\Base\Models\Module::getInstance($qualifiedModuleName);
		return $recordModel->setModule($moduleModel);
	}
}
