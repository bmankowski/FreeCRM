<?php

namespace App\Modules\OSSMailScanner\scanneractions;

/**
 * Mail scanner action bind OSSEmployees
 * @package YetiForce.MailScanner
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BindOSSEmployees extends \App\Runtime\Vtiger_Base_Model
{

	public function process(OSSMail_Mail_Model $mail, $moduleName = 'OSSEmployees')
	{
		return parent::process($mail, 'OSSEmployees');
	}
}
