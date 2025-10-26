<?php

namespace App\Modules\Base\Views;

/**
 * Basic Inventory View Class
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Http\Vtiger_Request;
class Inventory  extends \App\Modules\Base\Views\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showDiscounts');
		$this->exposeMethod('showTaxes');
	}

	public function showDiscounts(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$discountType = $request->get('discountType');
		$currency = $request->get('currency');
		$relatedRecord = $request->get('relatedRecord');
		$isIndividual = $request->get('isIndividual');
		$totalPrice = $request->get('totalPrice');

		$inventoryModel = \App\Modules\Base\Models\Inventory::getInstance($moduleName);
		$config = $inventoryModel->getDiscountsConfig();
		$groupDiscount = $inventoryModel->getAccountDiscount($relatedRecord);

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('GLOBAL_DISCOUNTS', $inventoryModel->getGlobalDiscounts());
		$viewer->assign('CURRENCY_SYMBOL', \vtlib\Functions::getCurrencySymbolandRate($currency)['symbol']);
		$viewer->assign('TOTAL_PRICE', $totalPrice);
		$viewer->assign('CONFIG', $config);
		$viewer->assign('DISCOUNT_TYPE', $discountType);
		$viewer->assign('AGGREGATION_TYPE', $config['aggregation']);
		$viewer->assign('AGGREGATION_INPUT_TYPE', $config['aggregation'] == 0 ? 'radio' : 'checkbox');
		$viewer->assign('GROUP_DISCOUNT', $groupDiscount['discount']);
		$viewer->assign('ACCOUNT_NAME', $groupDiscount['name']);
		echo $viewer->view('InventoryDiscounts.tpl', $moduleName, true);
	}

	/**
	 * Function to show taxes
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function showTaxes(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$recordModule = $request->get('recordModule');
		$currency = $request->get('currency');
		$sourceRecord = $request->get('sourceRecord');
		$taxType = $request->get('taxType');
		$totalPrice = $request->get('totalPrice');

		$inventoryModel = \App\Modules\Base\Models\Inventory::getInstance($moduleName);
		$accountTaxs = $inventoryModel->getAccountTax($moduleName, $sourceRecord);

		$config = $inventoryModel->getTaxesConfig();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $record);
		$viewer->assign('RECORD_MODULE', $recordModule);
		$viewer->assign('GLOBAL_TAXES', \App\Modules\Base\Models\Inventory::getGlobalTaxes());
		$viewer->assign('CURRENCY_SYMBOL', \vtlib\Functions::getCurrencySymbolandRate($currency)['symbol']);
		$viewer->assign('TOTAL_PRICE', $totalPrice);
		$viewer->assign('CONFIG', $config);
		$viewer->assign('TAX_TYPE', $taxType);
		$viewer->assign('TAX_FIELD', \App\Modules\Base\Models\InventoryField::getTaxField($recordModule));
		$viewer->assign('AGGREGATION_TYPE', $config['aggregation']);
		$viewer->assign('AGGREGATION_INPUT_TYPE', $config['aggregation'] == 0 ? 'radio' : 'checkbox');
		$viewer->assign('GROUP_TAXS', $accountTaxs['taxs']);
		$viewer->assign('ACCOUNT_NAME', $accountTaxs['name']);
		echo $viewer->view('InventoryTaxes.tpl', $moduleName, true);
	}
}
