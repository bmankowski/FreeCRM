<?php

namespace App\Modules\OSSMailScanner\scanneractions;

/**
 * Mail scanner action bind Competition
 * @package YetiForce.MailScanner
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BindCompetition extends \App\Runtime\Vtiger_Base_Model
{

	public function process(OSSMail_Mail_Model $mail, $moduleName = 'Competition')
	{
		return parent::process($mail, 'Competition');
	}
}
