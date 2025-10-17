<?php

namespace App\Modules\Leads;

/**
 * Total emails
 * @package YetiForce.SummaryBlock
 * @license licenses/License.html
 * @author YetiForce.com
 */
class TotalEmails {

	public $name = 'Total emails';
	public $sequence = 1;
	public $reference = 'OSSMailView';

	/**
	 * Function get number of emails
	 * @param \App\Modules\Vtiger\Models\Record $instance
	 * @return int - Number of emails
	 */
	public function process(\App\Modules\Vtiger\Models\Record $instance)
	{
		$relationListView = \App\Modules\Vtiger\Models\RelationListView::getInstance($instance, $this->reference);
		return (int) $relationListView->getRelatedEntriesCount();
	}
}
