<?php

namespace App\Modules\OSSMailScanner\scanneractions;

/**
 * Mail scanner action bind Leads
 * @package YetiForce.MailScanner
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BindLeads extends \App\Runtime\Vtiger_Base_Model
{

	public function process(OSSMail_Mail_Model $mail, $moduleName = 'Leads')
	{
		return parent::process($mail, 'Leads');
	}
}
