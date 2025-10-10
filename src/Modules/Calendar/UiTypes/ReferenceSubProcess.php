<?php

namespace FreeCRM\Modules\Calendar\UiTypes;

/**
 * UIType ReferenceSubProcess Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ReferenceSubProcess extends Base
{

	public function getReferenceList()
	{
		return ['SQuoteEnquiries', 'SRequirementsCards', 'SCalculations', 'SQuotes', 'SSingleOrders', 'SRecurringOrders', 'HelpDesk', 'SVendorEnquiries'];
	}
}
