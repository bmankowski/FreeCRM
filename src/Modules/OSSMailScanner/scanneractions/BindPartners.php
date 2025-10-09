<?php

namespace FreeCRM\Modules\OSSMailScanner;

/**
 * Mail scanner action bind Partners
 * @package YetiForce.MailScanner
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ScannerAction extends Model
{

	public function process(OSSMail_Mail_Model $mail, $moduleName = 'Partners')
	{
		return parent::process($mail, 'Partners');
	}
}
