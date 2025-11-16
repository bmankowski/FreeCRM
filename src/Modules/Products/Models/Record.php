<?php

namespace App\Modules\Products\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Record extends \App\Modules\Base\Models\Record
{

	/**
	 * Holds current mode (edit/create)
	 * @var string
	 */
	protected $mode = '';

	/**
	 * Function to get Taxes Url
	 * @return string Url
	 */
	public function getTaxesURL()
	{
		return 'index.php?module=Inventory&action=GetTaxes&record=' . $this->getId();
	}

	/**
	 * Function to get values of more currencies listprice
	 * @return <Array> of listprice values
	 */
	static function getListPriceValues($id)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$listPrice = $db->pquery('SELECT * FROM vtiger_productcurrencyrel WHERE productid = ?', [$id]);
		$listpriceValues = [];
		while ($row = $db->fetch_array($listPrice)) {
			$listpriceValues[$row['currencyid']] = \App\Fields\CurrencyField::convertToUserFormat($row['actual_price'], null, true);
		}
		return $listpriceValues;
	}

	/**
	 * Function to get subproducts for this record
	 * @return <Array> of subproducts
	 */
	public function getSubProducts()
	{
		$db = \App\Database\PearDatabase::getInstance();

		$result = $db->pquery("SELECT vtiger_products.productid FROM vtiger_products
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_products.productid
			LEFT JOIN vtiger_seproductsrel ON vtiger_seproductsrel.crmid = vtiger_products.productid AND vtiger_products.discontinued = 1 AND vtiger_seproductsrel.setype='Products'
			LEFT JOIN vtiger_users ON vtiger_users.id=vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_seproductsrel.productid = ? ", array($this->getId()));

		$subProductList = array();

		$numRowsCount = $db->num_rows($result);
		for ($i = 0; $i < $numRowsCount; $i++) {
			$subProductId = $db->query_result($result, $i, 'productid');
			$subProductList[] = \App\Modules\Base\Models\Record::getInstanceById($subProductId, 'Products');
		}

		return $subProductList;
	}

	/**
	 * Function to get price details
	 * @return <Array> List of prices
	 */
	public function getPriceDetails()
	{
		$priceDetails = $this->get('priceDetails');
		if (!empty($priceDetails)) {
			return $priceDetails;
		}
		$priceDetails = $this->getPriceDetailsForProduct($this->getId(), $this->get('unit_price'), 'available', $this->getModuleName());
		$this->set('priceDetails', $priceDetails);
		return $priceDetails;
	}

	/**
	 * Function to get base currency details
	 * @return <Array>
	 */
	public function getBaseCurrencyDetails()
	{
		$baseCurrencyDetails = $this->get('baseCurrencyDetails');
		if (!empty($baseCurrencyDetails)) {
			return $baseCurrencyDetails;
		}

		$recordId = $this->getId();
		if (!empty($recordId)) {
			$baseCurrency = $this->getProductBaseCurrency($recordId, $this->getModuleName());
		} else {
			$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
			$baseCurrency = \App\Utils\InventoryUtils::getUserCurrencyId($currentUserModel);
		}
		$baseCurrencyDetails = array('currencyid' => $baseCurrency);

		$baseCurrencySymbolDetails = \vtlib\Functions:: getCurrencySymbolandRate($baseCurrency);
		$baseCurrencyDetails = array_merge($baseCurrencyDetails, $baseCurrencySymbolDetails);
		$this->set('baseCurrencyDetails', $baseCurrencyDetails);

		return $baseCurrencyDetails;
	}

	/**
	 * Function to get Image Details
	 * @return <array> Image Details List
	 */
	public function getImageDetails()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$imageDetails = array();
		$recordId = $this->getId();

		if ($recordId) {
			$sql = "SELECT vtiger_attachments.*, vtiger_crmentity.setype FROM vtiger_attachments
						INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid
						WHERE vtiger_crmentity.setype = 'Products Image' AND vtiger_seattachmentsrel.crmid = ?";

			$result = $db->pquery($sql, array($recordId));
			$count = $db->num_rows($result);

			$imageOriginalNamesList = [];

			for ($i = 0; $i < $count; $i++) {
				$imageIdsList[] = $db->query_result($result, $i, 'attachmentsid');
				$imagePathList[] = $db->query_result($result, $i, 'path');
				$imageName = $db->query_result($result, $i, 'name');

				//decode_html - added to handle UTF-8 characters in file names
				$imageOriginalNamesList[] = \App\Utils\ListViewUtils::decodeHtml($imageName);

				//urlencode - added to handle special characters like #, %, etc.,
				$imageNamesList[] = $imageName;
			}

			if (is_array($imageOriginalNamesList)) {
				$countOfImages = count($imageOriginalNamesList);
				for ($j = 0; $j < $countOfImages; $j++) {
					$imageDetails[] = array(
						'id' => $imageIdsList[$j],
						'orgname' => $imageOriginalNamesList[$j],
						'path' => $imagePathList[$j] . $imageIdsList[$j],
						'name' => $imageNamesList[$j]
					);
				}
			}
		}
		return $imageDetails;
	}

	/**
	 * Static Function to get the list of records matching the search key
	 * @param string $searchKey
	 * @return <Array> - List of \App\Modules\Base\Models\Record or Module Specific Record Model instances
	 */
	public static function getSearchResult($searchKey, $moduleName = false, $limit = false, $operator = false)
	{
		$query = false;
		if ($moduleName !== false && ($moduleName == 'Products' || $moduleName == 'Services' )) {
			$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
			$adb = \App\Database\PearDatabase::getInstance();
			$params = ['%' . $currentUser->getId() . '%', "%$searchKey%"];
			$queryFrom = 'SELECT u_yf_crmentity_search_label.`crmid`,u_yf_crmentity_search_label.`setype`,u_yf_crmentity_search_label.`searchlabel` FROM `u_yf_crmentity_search_label`';
			$queryWhere = ' WHERE u_yf_crmentity_search_label.`userid` LIKE ? && u_yf_crmentity_search_label.`searchlabel` LIKE ?';
			$orderWhere = '';
			if ($moduleName !== false) {
				$multiMode = is_array($moduleName);
				if ($multiMode) {
					$queryWhere .= sprintf(' AND u_yf_crmentity_search_label.`setype` IN (%s)', $adb->generateQuestionMarks($moduleName));
					$params = array_merge($params, $moduleName);
				} else {
					$queryWhere .= ' && `setype` = ?';
					$params[] = $moduleName;
				}
			} elseif (\App\Core\AppConfig::search('GLOBAL_SEARCH_SORTING_RESULTS') == 2) {
				$queryFrom .= ' LEFT JOIN vtiger_entityname ON vtiger_entityname.modulename = u_yf_crmentity_search_label.setype';
				$queryWhere .= ' && vtiger_entityname.`turn_off` = 1 ';
				$orderWhere = ' vtiger_entityname.sequence';
			}
			if ($moduleName == 'Products') {
				$queryFrom .= ' INNER JOIN vtiger_products ON vtiger_products.productid = u_yf_crmentity_search_label.crmid';
				$queryWhere .= ' && vtiger_products.discontinued = 1';
			} else if ($moduleName == 'Services') {
				$queryFrom .= ' INNER JOIN vtiger_service ON vtiger_service.serviceid = u_yf_crmentity_search_label.crmid';
				$queryWhere .= ' && vtiger_service.discontinued = 1';
			}
			$query = $queryFrom . $queryWhere;
			if (!empty($orderWhere)) {
				$query .= sprintf(' ORDER BY %s', $orderWhere);
			}
			if (!$limit) {
				$limit = \App\Core\AppConfig::search('GLOBAL_SEARCH_MODAL_MAX_NUMBER_RESULT');
			}
			if ($limit) {
				$query .= ' LIMIT ';
				$query .= $limit;
			}
		}

		$rows = [];
		if (!$query) {
			$recordSearch = new \App\RecordSearch($searchKey, $moduleName, $limit);
			if ($operator) {
				$recordSearch->operator = $operator;
			}
			$rows = $recordSearch->search();
		} else {
			$result = $adb->pquery($query, $params);
			while ($row = $adb->getRow($result)) {
				$rows[] = $row;
			}
		}
		$ids = $matchingRecords = $leadIdsList = [];
		foreach ($rows as &$row) {
			$ids[] = $row['crmid'];
			if ($row['setype'] === 'Leads') {
				$leadIdsList[] = $row['crmid'];
			}
		}
		$convertedInfo = \App\Modules\Leads\Models\Module::getConvertedInfo($leadIdsList);
		$labels = \App\Records\Record::getLabel($ids);

		foreach ($rows as &$row) {
			if ($row['setype'] === 'Leads' && $convertedInfo[$row['crmid']]) {
				continue;
			}
			$recordMeta = \vtlib\Functions:: getCRMRecordMetadata($row['crmid']);
			$row['id'] = $row['crmid'];
			$row['label'] = $labels[$row['crmid']];
			$row['smownerid'] = $recordMeta['smownerid'];
			$row['createdtime'] = $recordMeta['createdtime'];
			$row['permitted'] = \App\Security\Privilege::isPermitted($row['setype'], 'DetailView', $row['crmid']);
			$moduleName = $row['setype'];
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
			$modelClassName = \App\Core\Loader::getComponentClassName('Model', 'Record', $moduleName);
			$recordInstance = new $modelClassName();
			$matchingRecords[$moduleName][$row['id']] = $recordInstance->setData($row)->setModuleFromInstance($moduleModel);
		}
		return $matchingRecords;
	}

	/**
	 * Function to get acive status of record
	 */
	public function getActiveStatusOfRecord()
	{
		$activeStatus = $this->get('discontinued');
		if ($activeStatus) {
			return $activeStatus;
		}
		$recordId = $this->getId();
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT discontinued FROM vtiger_products WHERE productid = ?', array($recordId));
		$activeStatus = $db->query_result($result, 'discontinued');
		return $activeStatus;
	}

	/**
	 * Function updates ListPrice for Product/Service-PriceBook relation
	 * @param <Integer> $relatedRecordId - PriceBook Id
	 * @param <Integer> $price - listprice
	 * @param <Integer> $currencyId - currencyId
	 */
	public function updateListPrice($relatedRecordId, $price, $currencyId)
	{
		$isExists = (new \App\Db\Query())->from('vtiger_pricebookproductrel')->where(['pricebookid' => $relatedRecordId, 'productid' => $this->getId()])->exists();
		if ($isExists) {
			\App\Db\Db::getInstance()->createCommand()
				->update('vtiger_pricebookproductrel', ['listprice' => $price], ['pricebookid' => $relatedRecordId, 'productid' => $this->getId()])
				->execute();
		} else {
			\App\Db\Db::getInstance()->createCommand()
				->insert('vtiger_pricebookproductrel', [
					'pricebookid' => $relatedRecordId,
					'productid' => $this->getId(),
					'listprice' => $price,
					'usedcurrency' => $currencyId
				])->execute();
		}
	}

	public function getPriceDetailsForProduct($productid, $unit_price, $available = 'available', $itemtype = 'Products')
	{
		$adb = \App\Database\PearDatabase::getInstance();

		\App\Log\Log::trace("Entering into function getPriceDetailsForProduct($productid)");
		if ($productid != '') {
			$product_currency_id = $this->getProductBaseCurrency($productid, $itemtype);
			$product_base_conv_rate = $this->getBaseConversionRateForProduct($productid, 'edit', $itemtype);
			// Detail View
			if ($available == 'available_associated') {
				$query = "select vtiger_currency_info.*, vtiger_productcurrencyrel.converted_price, vtiger_productcurrencyrel.actual_price
					from vtiger_currency_info
					inner join vtiger_productcurrencyrel on vtiger_currency_info.id = vtiger_productcurrencyrel.currencyid
					where vtiger_currency_info.currency_status = 'Active' and vtiger_currency_info.deleted=0
					and vtiger_productcurrencyrel.productid = ? and vtiger_currency_info.id != ?";
				$params = array($productid, $product_currency_id);
			} else { // Edit View
				$query = "select vtiger_currency_info.*, vtiger_productcurrencyrel.converted_price, vtiger_productcurrencyrel.actual_price
					from vtiger_currency_info
					left join vtiger_productcurrencyrel
					on vtiger_currency_info.id = vtiger_productcurrencyrel.currencyid and vtiger_productcurrencyrel.productid = ?
					where vtiger_currency_info.currency_status = 'Active' and vtiger_currency_info.deleted=0";
				$params = array($productid);
			}

			$res = $adb->pquery($query, $params);
			$rows_rew = $adb->num_rows($res);
			for ($i = 0; $i < $rows_rew; $i++) {
				$price_details[$i]['productid'] = $productid;
				$price_details[$i]['currencylabel'] = $adb->query_result($res, $i, 'currency_name');
				$price_details[$i]['currencycode'] = $adb->query_result($res, $i, 'currency_code');
				$price_details[$i]['currencysymbol'] = $adb->query_result($res, $i, 'currency_symbol');
				$currency_id = $adb->query_result($res, $i, 'id');
				$price_details[$i]['curid'] = $currency_id;
				$price_details[$i]['curname'] = 'curname' . $adb->query_result($res, $i, 'id');
				$cur_value = $adb->query_result($res, $i, 'actual_price');

				// Get the conversion rate for the given currency, get the conversion rate of the product currency to base currency.
				// Both together will be the actual conversion rate for the given currency.
				$conversion_rate = $adb->query_result($res, $i, 'conversion_rate');
				$actual_conversion_rate = $product_base_conv_rate * $conversion_rate;

				$is_basecurrency = false;
				if ($currency_id == $product_currency_id) {
					$is_basecurrency = true;
				}

				if ($cur_value === null || $cur_value == '') {
					$price_details[$i]['check_value'] = false;
					if ($unit_price != null) {
						$cur_value = \App\Fields\CurrencyField::convertFromMasterCurrency($unit_price, $actual_conversion_rate);
					} else {
						$cur_value = '0';
					}
				} else {
					$price_details[$i]['check_value'] = true;
				}
				$price_details[$i]['curvalue'] = \App\Fields\CurrencyField::convertToUserFormat($cur_value, null, true);
				$price_details[$i]['conversionrate'] = $actual_conversion_rate;
				$price_details[$i]['is_basecurrency'] = $is_basecurrency;
			}
		} else {
			if ($available == 'available') { // Create View
				$currentUser = \App\User\CurrentUser::get();
				$userCurrencyId = \App\Utils\InventoryUtils::getUserCurrencyId($currentUser);

				$query = "select vtiger_currency_info.* from vtiger_currency_info
					where vtiger_currency_info.currency_status = 'Active' and vtiger_currency_info.deleted=0";
				$params = array();

				$res = $adb->pquery($query, $params);
				$rows_res = $adb->num_rows($res);
				for ($i = 0; $i < $rows_res; $i++) {
					$price_details[$i]['currencylabel'] = $adb->query_result($res, $i, 'currency_name');
					$price_details[$i]['currencycode'] = $adb->query_result($res, $i, 'currency_code');
					$price_details[$i]['currencysymbol'] = $adb->query_result($res, $i, 'currency_symbol');
					$currency_id = $adb->query_result($res, $i, 'id');
					$price_details[$i]['curid'] = $currency_id;
					$price_details[$i]['curname'] = 'curname' . $adb->query_result($res, $i, 'id');

					// Get the conversion rate for the given currency, get the conversion rate of the product currency(logged in user's currency) to base currency.
					// Both together will be the actual conversion rate for the given currency.
					$conversion_rate = $adb->query_result($res, $i, 'conversion_rate');
					$userCurrencyData = \vtlib\Functions:: getCurrencySymbolandRate($userCurrencyId);
					$userRate = (float) ($userCurrencyData['rate'] ?? 0);
					if ($userRate <= 0) {
						$userRate = 1;
					}
					$product_base_conv_rate = 1 / $userRate;
					$actual_conversion_rate = $product_base_conv_rate * $conversion_rate;

					$price_details[$i]['check_value'] = false;
					$price_details[$i]['curvalue'] = '0';
					$price_details[$i]['conversionrate'] = $actual_conversion_rate;

					$is_basecurrency = false;
					if ($currency_id == $userCurrencyId) {
						$is_basecurrency = true;
					}
					$price_details[$i]['is_basecurrency'] = $is_basecurrency;
				}
			} else {
				\App\Log\Log::trace("Product id is empty. we cannot retrieve the associated prices.");
			}
		}

		\App\Log\Log::trace("Exit from function getPriceDetailsForProduct($productid)");
		return $price_details;
	}

	public function getProductBaseCurrency($productid, $module = 'Products')
	{
		$adb = \App\Database\PearDatabase::getInstance();
		if ($module == 'Services') {
			$sql = 'select currency_id from vtiger_service where serviceid=?';
		} else {
			$sql = 'select currency_id from vtiger_products where productid=?';
		}
		$res = $adb->pquery($sql, [$productid]);
		$currencyid = $adb->query_result($res, 0, 'currency_id');
		return $currencyid;
	}

	public function getBaseConversionRateForProduct($productid, $mode = 'edit', $module = 'Products')
	{
		return \App\Utils\InventoryUtils::getBaseConversionRateForProduct($productid, $mode, $module);
	}

	/**
	 * The function decide about mandatory save record
	 * @return type
	 */
	public function isMandatorySave()
	{
		return $_FILES ? true : false;
	}

	/**
	 * Custom Save for Module
	 */
	public function saveToDb($relationParams = null, \App\Http\Vtiger_Request $request = null)
	{
		if ($request === null) {
			// Request should be passed as parameter
		}
		if ($request) {
			$this->mode = (string) $request->get('mode');
		}
		parent::saveToDb();
		//Inserting into product_taxrel table
		if ($request && $request->get('ajxaction') != 'DETAILVIEW' && $request->get('action') != 'MassSave' && $request->get('action') != 'ProcessDuplicates') {
			$this->insertPriceInformation($request);
		}
		// Update unit price value in vtiger_productcurrencyrel
		$this->updateUnitPrice();
		//Inserting into attachments
		if ($request && $request->get('module') === 'Products') {
			$this->insertAttachment($request);
		}
	}

	/**
	 * Update unit price 
	 */
	public function updateUnitPrice()
	{
		$productInfo = (new \App\Db\Query())->select(['unit_price', 'currency_id'])
			->from($this->getEntity()->table_name)
			->where([$this->getEntity()->table_index => $this->getId()])
			->one();
		\App\Db\Db::getInstance()->createCommand()->update('vtiger_productcurrencyrel', ['actual_price' => $productInfo['unit_price']], ['productid' => $this->getId(), 'currencyid' => $productInfo['currency_id']])->execute();
	}

	/**
	 * Function to save the product price information in vtiger_productcurrencyrel table
	 */
	public function insertPriceInformation(\App\Http\Vtiger_Request $request = null)
	{
		if ($request === null) {
			// Request should be passed as parameter
		}
		\App\Log\Log::trace('Entering ' . __METHOD__);
		$db = \App\Db\Db::getInstance();
		$mode = $this->mode ?: ($request ? (string) $request->get('mode') : '');
		if ($mode === '' && !$this->isNew()) {
			$mode = 'edit';
		}
		$productBaseConvRate = \App\Utils\InventoryUtils::getBaseConversionRateForProduct($this->getId(), $mode);
		$currencySet = false;
		$currencyDetails = \vtlib\Functions:: getAllCurrency(true);
		if (!$this->isNew()) {
			$db->createCommand()->delete('vtiger_productcurrencyrel', ['productid' => $this->getId()])->execute();
		}
		foreach ($currencyDetails as $curid => $currency) {
			$curName = $currency['currency_name'];
			$curCheckName = 'cur_' . $curid . '_check';
			$curValue = 'curname' . $curid;
			if ($request->get($curCheckName) === 'on' || $request->get($curCheckName) === 1) {
				$requestPrice = \App\Fields\CurrencyField::convertToDBFormat($request->get('unit_price'), null, true);
				$actualPrice = \App\Fields\CurrencyField::convertToDBFormat($request->get($curValue), null, true);
				$actualConversionRate = $productBaseConvRate * $currency['conversion_rate'];
				$convertedPrice = $actualConversionRate * $requestPrice;
				\App\Log\Log::trace("Going to save the Product - $curName currency relationship");
				\App\Db\Db::getInstance()->createCommand()->insert('vtiger_productcurrencyrel', [
					'productid' => $this->getId(),
					'currencyid' => $curid,
					'converted_price' => $convertedPrice,
					'actual_price' => $actualPrice
				])->execute();
				if ($request->get('base_currency') === $curValue) {
					$currencySet = true;
					$db->createCommand()
						->update($this->getEntity()->table_name, ['currency_id' => $curid, 'unit_price' => $actualPrice], [$this->getEntity()->table_index => $this->getId()])
						->execute();
				}
			}
		}
		if (!$currencySet) {
			reset($currencyDetails);
			$curid = key($currencyDetails);
			$db->createCommand()
				->update($this->getEntity()->table_name, ['currency_id' => $curid], [$this->getEntity()->table_index => $this->getId()])
				->execute();
		}
		\App\Log\Log::trace('Exiting ' . __METHOD__);
	}

	/**
	 * This function is used to add the vtiger_attachments. This will call the function uploadAndSaveFile which will upload the attachment into the server and save that attachment information in the database.
	 */
	public function insertAttachment(\App\Http\Vtiger_Request $request = null)
	{
		if ($request === null) {
			// Request should be passed as parameter
		}
		$db = \App\Db\Db::getInstance();
		$id = $this->getId();
		$module = $request->get('module');
		$mode = $request->get('mode');
		\App\Log\Log::trace("Entering into insertIntoAttachment($id,$module) method.");
		foreach ($_FILES as $fileindex => $files) {
			if (empty($files['tmp_name'])) {
				continue;
			}
			$fileInstance = \App\Fields\File::loadFromRequest($files);
			if ($fileInstance->validate('image')) {
				if ($request->get($fileindex . '_hidden') != '')
					$files['original_name'] = $request->get($fileindex . '_hidden');
				else
					$files['original_name'] = stripslashes($files['name']);
				$files['original_name'] = str_replace('"', '', $files['original_name']);
				$fileId = $request->get('fileid');
				$this->uploadAndSaveFile($files, 'Attachment', $module, $mode, $fileId);
			}
		}
		//Updating image information in main table of products
		$dataReader = (new \App\Db\Query())->select(['name'])->from('vtiger_seattachmentsrel')
				->innerJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid')
				->leftJoin('vtiger_products', 'vtiger_products.productid = vtiger_seattachmentsrel.crmid')
				->where(['vtiger_seattachmentsrel.crmid' => $id])
				->createCommand()->query();
		$productImageMap = [];
		while ($imageName = $dataReader->readColumn(0)) {
			$productImageMap [] = \App\Utils\ListViewUtils::decodeHtml($imageName);
		}
		$db->createCommand()->update('vtiger_products', ['imagename' => implode(",", $productImageMap)], ['productid' => $id])
			->execute();
		//Remove the deleted vtiger_attachments from db - Products
		if ($module === 'Products' && $request->get('del_file_list') != '') {
			$deleteFileList = explode("###", trim($request->get('del_file_list'), "###"));
			foreach ($deleteFileList as $fileName) {
				$attachmentId = (new \App\Db\Query())->select(['vtiger_attachments.attachmentsid'])
					->from('vtiger_attachments')
					->innerJoin('vtiger_seattachmentsrel', 'vtiger_attachments.attachmentsid = vtiger_seattachmentsrel.attachmentsid')
					->where(['crmid' => $id, 'name' => $fileName])
					->scalar();
				$db->createCommand()->delete('vtiger_attachments', ['attachmentsid' => $attachmentId])->execute();
				$db->createCommand()->delete('vtiger_seattachmentsrel', ['attachmentsid' => $attachmentId])->execute();
			}
		}
		\App\Log\Log::trace("Exiting from insertIntoAttachment($id,$module) method.");
	}
}
