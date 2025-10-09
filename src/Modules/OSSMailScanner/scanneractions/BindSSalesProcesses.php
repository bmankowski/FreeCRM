<?php

namespace FreeCRM\Modules\OSSMailScanner;

/**
 * Mail scanner action bind SalesProcesses
 * @package YetiForce.MailScanner
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ScannerAction extends Model
{

	public $moduleName = 'SSalesProcesses';
	public $tableName = 'u_yf_ssalesprocesses';
	public $tableColumn = 'ssalesprocesses_no';

	public function process(OSSMail_Mail_Model $mail)
	{
		$this->mail = $mail;
		return parent::findAndBind();
	}
}
