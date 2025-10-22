<?php

namespace App\Modules\OSSMailScanner\scanneractions;

/**
 * Mail scanner action bind Vendors
 * @package YetiForce.MailScanner
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BindVendors extends \App\Runtime\BaseModel
{

	public function process(OSSMail_Mail_Model $mail, $moduleName = 'Vendors')
	{
		return parent::process($mail, 'Vendors');
	}
}
