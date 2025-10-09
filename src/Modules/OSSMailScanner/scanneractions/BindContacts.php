<?php

namespace FreeCRM\Modules\OSSMailScanner\scanneractions;

/**
 * Mail scanner action bind Contacts
 * @package YetiForce.MailScanner
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class BindContacts extends Model
{

	public function process(OSSMail_Mail_Model $mail, $moduleName = 'Contacts')
	{
		return parent::process($mail, 'Contacts');
	}
}
