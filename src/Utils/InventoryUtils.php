<?php
/**
 * FreeCRM Inventory Utilities
 */

namespace App\Utils;

class InventoryUtils
{
	/**
	 * Resolve currency id for given user (falls back to base currency)
	 */
	public static function getUserCurrencyId(?\App\Modules\Users\Models\Record $user = null): int
	{
		$userId = null;
		if ($user) {
			if (method_exists($user, 'getId')) {
				$userId = $user->getId();
			} elseif (isset($user->id)) {
				$userId = (int) $user->id;
			}
		}
		if (!$userId) {
			$userId = \App\Modules\Users\Models\Record::getCurrentUserId();
		}
		if ($userId) {
			$currencyId = \vtlib\Functions:: userCurrencyId($userId);
			if ($currencyId) {
				return (int) $currencyId;
			}
		}
		$baseCurrency = \App\Modules\Base\Helpers\Util::getBaseCurrency();
		if ($baseCurrency && isset($baseCurrency['id'])) {
			return (int) $baseCurrency['id'];
		}
		$fallbackId = (new \App\Db\Query())->select('id')->from('vtiger_currency_info')
			->where(['currency_status' => 'Active'])
			->orderBy(['is_default' => SORT_DESC])
			->scalar();
		return (int) ($fallbackId ?: 1);
	}

	/**
	 * Get the currency information for an inventory entity (PO or Invoice)
	 * @param string $module Module name
	 * @param int $id Entity ID
	 * @return array Currency information
	 */
	public static function getInventoryCurrencyInfo($module, $id)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		\App\Log\Log::trace("Entering into function getInventoryCurrencyInfo($module, $id).");

		$focus = new $module();

		$res = $adb->pquery("select currency_id, {$focus->table_name}.conversion_rate as conv_rate, vtiger_currency_info.* from {$focus->table_name} "
			. "inner join vtiger_currency_info on {$focus->table_name}.currency_id = vtiger_currency_info.id where {$focus->table_index}=?", array($id), true);

		$currency_info = [];
		$currency_info['currency_id'] = $adb->query_result($res, 0, 'currency_id');
		$currency_info['conversion_rate'] = $adb->query_result($res, 0, 'conv_rate');
		$currency_info['currency_name'] = $adb->query_result($res, 0, 'currency_name');
		$currency_info['currency_code'] = $adb->query_result($res, 0, 'currency_code');
		$currency_info['currency_symbol'] = $adb->query_result($res, 0, 'currency_symbol');

		\App\Log\Log::trace("Exit from function getInventoryCurrencyInfo($module, $id).");

		return $currency_info;
	}

	/**
	 * Get the list of all currencies as an array
	 * @param string $available 'all' returns all currencies, 'available' returns only available currencies
	 * @return array Currency details
	 */
	public static function getAllCurrencies($available = 'available')
	{
		return \vtlib\Functions:: getAllCurrency($available != 'all');
	}

	/**
	 * Get all price details for different currencies associated to a product
	 * @param int $productid Product ID
	 * @param decimal $unit_price Unit price of the product
	 * @param string $available 'available' or 'available_associated'
	 * @param string $itemtype 'Products' or 'Services'
	 * @return array Price details
	 */
	public static function getPriceDetailsForProduct($productid, $unit_price, $available = 'available', $itemtype = 'Products')
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$price_details = [];

		\App\Log\Log::trace("Entering into function getPriceDetailsForProduct($productid)");
		if ($productid != '') {
			$product_currency_id = self::getProductBaseCurrency($productid, $itemtype);
			$product_base_conv_rate = self::getBaseConversionRateForProduct($productid, 'edit', $itemtype);
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
			$rows_res = $adb->num_rows($res);
			for ($i = 0; $i < $rows_res; $i++) {
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
				}
				$price_details[$i]['check_value'] = true;
				$price_details[$i]['curvalue'] = \App\Fields\CurrencyField::convertToUserFormat($cur_value, null, true);
				$price_details[$i]['conversionrate'] = $actual_conversion_rate;
				$price_details[$i]['is_basecurrency'] = $is_basecurrency;
			}
		} else {
			if ($available == 'available') { // Create View
				$currentUser = \App\User\CurrentUser::get();
				$userCurrencyId = self::getUserCurrencyId($currentUser);

				$query = "select vtiger_currency_info.* from vtiger_currency_info
					where vtiger_currency_info.currency_status = 'Active' and vtiger_currency_info.deleted=0";
				$params = [];

				$res = $adb->pquery($query, $params);
				$rows = $adb->num_rows($res);
				for ($i = 0; $i < $rows; $i++) {
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

	/**
	 * Get the base currency ID used for the given product
	 * @param int $productid Product ID
	 * @param string $module 'Products' or 'Services'
	 * @return int Currency ID
	 */
	public static function getProductBaseCurrency($productid, $module = 'Products')
	{
		$adb = \App\Database\PearDatabase::getInstance();

		if ($module == 'Services') {
			$sql = "select currency_id from vtiger_service where serviceid=?";
		} else {
			$sql = "select currency_id from vtiger_products where productid=?";
		}
		$params = array($productid);
		$res = $adb->pquery($sql, $params);
		$currencyid = $adb->query_result($res, 0, 'currency_id');
		return $currencyid;
	}

	/**
	 * Get the conversion rate for the product base currency with respect to the CRM base currency
	 * @param int $productid Product ID
	 * @param string $mode Mode ('edit' or other)
	 * @param string $module 'Products' or 'Services'
	 * @return number Conversion rate
	 */
	public static function getBaseConversionRateForProduct($productid, $mode = 'edit', $module = 'Products')
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$nameCache = $productid . $mode . $module;
		if (\App\Cache\Cache::has('getBaseConversionRateForProduct', $nameCache)) {
			$convRate = \App\Cache\Cache::get('getBaseConversionRateForProduct', $nameCache);
			return $convRate;
		}
		$currentUser = \App\User\CurrentUser::get();
		if ($mode == 'edit') {
			if ($module == 'Services') {
				$sql = "select conversion_rate from vtiger_service inner join vtiger_currency_info
					on vtiger_service.currency_id = vtiger_currency_info.id where vtiger_service.serviceid=?";
			} else {
				$sql = "select conversion_rate from vtiger_products inner join vtiger_currency_info
					on vtiger_products.currency_id = vtiger_currency_info.id where vtiger_products.productid=?";
			}
			$params = array($productid);
		} else {
			$sql = "select conversion_rate from vtiger_currency_info where id=?";
			$params = array(self::getUserCurrencyId($currentUser));
		}

		$result = $adb->pquery($sql, $params);
		$convRate = (float) $adb->getSingleValue($result);
		if ($convRate <= 0) {
			$convRate = 1;
		}
		$convRate = 1 / $convRate;
		\App\Cache\Cache::save('getBaseConversionRateForProduct', $nameCache, $convRate);
		return $convRate;
	}

	/**
	 * Get prices for given list of products based on specified currency
	 * @param int $currencyid Currency ID
	 * @param array $productIds List of product IDs
	 * @param string $module 'Products' or 'Services'
	 * @return array Prices list (product ID => price)
	 */
	public static function getPricesForProducts($currencyid, $productIds, $module = 'Products')
	{
		$priceList = [];
		if (count($productIds) > 0) {
			if ($module == 'Services') {
				$dataReader = (new \App\Db\Query())->select(['vtiger_currency_info.id', 'vtiger_currency_info.conversion_rate',
							'productid' => 'vtiger_service.serviceid', 'vtiger_service.unit_price', 'vtiger_productcurrencyrel.actual_price'])
						->from('vtiger_service')
						->leftJoin('vtiger_productcurrencyrel', 'vtiger_service.serviceid = vtiger_productcurrencyrel.productid')
						->leftJoin('vtiger_currency_info', 'vtiger_currency_info.id = vtiger_productcurrencyrel.currencyid')
						->where(['vtiger_service.serviceid' => $productIds, 'vtiger_currency_info.id' => $currencyid])
						->createCommand()->query();
			} else {
				$dataReader = (new \App\Db\Query())->select(['vtiger_currency_info.id', 'vtiger_currency_info.conversion_rate',
							'vtiger_products.productid', 'vtiger_products.unit_price', 'vtiger_productcurrencyrel.actual_price'])
						->from('vtiger_products')
						->leftJoin('vtiger_productcurrencyrel', 'vtiger_products.productid = vtiger_productcurrencyrel.productid')
						->leftJoin('vtiger_currency_info', 'vtiger_currency_info.id = vtiger_productcurrencyrel.currencyid')
						->where(['vtiger_products.productid' => $productIds, 'vtiger_currency_info.id' => $currencyid])
						->createCommand()->query();
			}

			while ($row = $dataReader->read()) {
				$productId = $row['productid'];
				if (\App\Fields\Field::getFieldPermission($module, 'unit_price')) {
					$actualPrice = (float) $row['actual_price'];
					if ($actualPrice === null || $actualPrice == '') {
						$actualPrice = $row['unit_price'] * $row['conversion_rate'] * self::getBaseConversionRateForProduct($productId, 'edit', $module);
					}
					$priceList[$productId] = $actualPrice;
				} else {
					$priceList[$productId] = '';
				}
			}
		}
		return $priceList;
	}
}

