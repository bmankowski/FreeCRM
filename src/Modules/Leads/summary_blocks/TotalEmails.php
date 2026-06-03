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
	public $reference = 'Mail';

	/**
	 * Function get number of emails
	 * @param \App\Modules\Base\Models\Record $instance
	 * @return int - Number of emails
	 */
	public function process(\App\Modules\Base\Models\Record $instance)
	{
		$relationListView = \App\Modules\Base\Models\RelationListView::getInstance($instance, $this->reference);
		return (int) $relationListView->getRelatedEntriesCount();
	}
}
