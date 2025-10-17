<?php

namespace App\Modules\Accounts;

/**
 * Invoice Header Field Class
 * @package YetiForce.HeaderField
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class HeaderField {

	public function process(\App\Modules\Vtiger\Models\DetailView $viewModel)
	{
		$row = (new \App\Db\Query())->select('MAX(saledate) AS date, SUM(sum_total) as total')->from('u_#__finvoice')
			->innerJoin('vtiger_crmentity', 'u_#__finvoice.finvoiceid = vtiger_crmentity.crmid')
			->where(['deleted' => 0, 'accountid' => $viewModel->getRecord()->getId()])->one();
		if (!empty($row['date']) && !empty($row['total'])) {
			return [
				'class' => 'btn-success',
				'title' => \App\Runtime\Vtiger_Language_Handler::translate('Sum invoices') . ': ' . CurrencyField::convertToUserFormat($row['total'], null, true),
				'badge' => DateTimeField::convertToUserFormat($row['date'])
			];
		}
		return false;
	}
}
