<?php

namespace App\Modules\FInvoice\Models;

/**
 * FInvoice Record Model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Record extends \App\Modules\Vtiger\Models\Record
{

	public function saveToDb($relationParams = null)
	{
		parent::saveToDb();

		if (\App\AppConfig::module('FInvoice', 'UPDATE_LAST_INVOICE_DATE') && !$this->isEmpty('accountid')) {
			$date = (new \App\Db\Query())->from('u_#__finvoice')
				->leftJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_#__finvoice.finvoiceid')
				->where(['vtiger_crmentity.deleted' => 0, 'accountid' => $this->get('accountid')])
				->max('saledate');
			if (!empty($date)) {
				\App\Db::getInstance()->createCommand()->update('vtiger_account', [
					'last_invoice_date' => $date
					], ['accountid' => $this->get('accountid')]
				)->execute();
			}
		}
	}
}
