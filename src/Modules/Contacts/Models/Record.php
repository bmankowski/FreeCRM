<?php

namespace App\Modules\Contacts\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Record extends \App\Modules\Base\Models\Record
{

	/** {@inheritdoc} */
	public function getRelatedListLeftSideLinks(\App\Modules\Base\Models\Record $parentRecord, array $context): array
	{
		$prepend = \App\Modules\Contacts\Models\RelatedListLeftSideMail::asLinks($this->getId(), $context);

		return array_merge($prepend, parent::getRelatedListLeftSideLinks($parentRecord, $context));
	}

	/**
	 * Function returns the url for create event
	 * @return string
	 */
	public function getCreateEventUrl()
	{
		$calendarModuleModel = \App\Modules\Base\Models\Module::getInstance('Calendar');
		return $calendarModuleModel->getCreateEventRecordUrl() . '&link=' . $this->getId();
	}

	/**
	 * Function returns the url for create todo
	 * @return string
	 */
	public function getCreateTaskUrl()
	{
		$calendarModuleModel = \App\Modules\Base\Models\Module::getInstance('Calendar');
		return $calendarModuleModel->getCreateTaskRecordUrl() . '&link=' . $this->getId();
	}

	/**
	 * Function to get List of Fields which are related from Contacts to Inventory Record
	 * @return array
	 */
	public function getInventoryMappingFields()
	{
		return array(
			array('parentField' => 'parent_id', 'inventoryField' => 'account_id', 'defaultValue' => ''),
			array('parentField' => 'buildingnumbera', 'inventoryField' => 'buildingnumbera', 'defaultValue' => ''),
			array('parentField' => 'localnumbera', 'inventoryField' => 'localnumbera', 'defaultValue' => ''),
			array('parentField' => 'addresslevel1a', 'inventoryField' => 'addresslevel1a', 'defaultValue' => ''),
			array('parentField' => 'addresslevel2a', 'inventoryField' => 'addresslevel2a', 'defaultValue' => ''),
			array('parentField' => 'addresslevel3a', 'inventoryField' => 'addresslevel3a', 'defaultValue' => ''),
			array('parentField' => 'addresslevel4a', 'inventoryField' => 'addresslevel4a', 'defaultValue' => ''),
			array('parentField' => 'addresslevel5a', 'inventoryField' => 'addresslevel5a', 'defaultValue' => ''),
			array('parentField' => 'addresslevel6a', 'inventoryField' => 'addresslevel6a', 'defaultValue' => ''),
			array('parentField' => 'addresslevel7a', 'inventoryField' => 'addresslevel7a', 'defaultValue' => ''),
			array('parentField' => 'addresslevel8a', 'inventoryField' => 'addresslevel8a', 'defaultValue' => ''),
			array('parentField' => 'buildingnumberb', 'inventoryField' => 'buildingnumberb', 'defaultValue' => ''),
			array('parentField' => 'localnumberb', 'inventoryField' => 'localnumberb', 'defaultValue' => ''),
			array('parentField' => 'addresslevel1b', 'inventoryField' => 'addresslevel1b', 'defaultValue' => ''),
			array('parentField' => 'addresslevel2b', 'inventoryField' => 'addresslevel2b', 'defaultValue' => ''),
			array('parentField' => 'addresslevel3b', 'inventoryField' => 'addresslevel3b', 'defaultValue' => ''),
			array('parentField' => 'addresslevel4b', 'inventoryField' => 'addresslevel4b', 'defaultValue' => ''),
			array('parentField' => 'addresslevel5b', 'inventoryField' => 'addresslevel5b', 'defaultValue' => ''),
			array('parentField' => 'addresslevel6b', 'inventoryField' => 'addresslevel6b', 'defaultValue' => ''),
			array('parentField' => 'addresslevel7b', 'inventoryField' => 'addresslevel7b', 'defaultValue' => ''),
			array('parentField' => 'addresslevel8b', 'inventoryField' => 'addresslevel8b', 'defaultValue' => ''),
		);
	}

	/**
	 * Function to get Image Details
	 * @return array Image Details List
	 */
	public function getImageDetails()
	{
		return \App\Models\RecordFile::getImageDetailsForRecord((int) $this->getId(), 'Contacts');
	}

	/**
	 * The function decide about mandatory save record
	 * @return mixed
	 */
	public function isMandatorySave()
	{
		return $_FILES ? true : false;
	}

	/**
	 * Function to save data to database
	 */
	public function saveToDb($relationParams = null, \App\Http\Vtiger_Request $request = null)
	{
		if ($request === null) {
			// Request should be passed as parameter
		}
		parent::saveToDb();
		$this->insertAttachment($request);
	}

	/**
	 * This function uploads an image via uploadAndSaveFile (s_yf_record_files).
	 */
	public function insertAttachment(\App\Http\Vtiger_Request $request = null)
	{
		if ($request === null) {
			// Request should be passed as parameter - skip attachment processing
			return;
		}
		$module = $request->get('module');
		$mode = $request->get('mode');
		$id = $this->getId();
		$db = \App\Db\Db::getInstance();
		$fileSaved = false;
		if ($_FILES) {
			foreach ($_FILES as $fileindex => $files) {
				if (empty($files['tmp_name'])) {
					continue;
				}
				$fileInstance = \App\Fields\File::loadFromRequest($files);
				if ($fileInstance->validate('image')) {
					$files['original_name'] = $request->get($fileindex . '_hidden');
					$fileSaved = $this->uploadAndSaveFile($files, 'Attachment', $module, $mode, $request->get('fileid'));
				}
			}
		}
		$row = \App\Models\RecordFile::getByRecord((int) $id, \App\Models\RecordFile::ROLE_IMAGE);
		$imageName = $row ? \App\Utils\ListViewUtils::decodeHtml((string) ($row['original_name'] ?? '')) : '';
		$db->createCommand()->update('vtiger_contactdetails', ['imagename' => $imageName], ['contactid' => $id])
			->execute();

		\App\Log\Log::trace("Exiting from insertIntoAttachment($id,$module) method.");
	}
}

