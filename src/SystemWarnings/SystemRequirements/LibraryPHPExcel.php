<?php
namespace App\SystemWarnings\SystemRequirements;


/**
 * Privilege File basic class
 * @package YetiForce.SystemWarnings
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class LibraryPHPExcel extends \App\SystemWarnings\Template
{

	protected $title = 'LBL_LIBRARY_PHPEXCEL';
	protected $priority = 4;

	/**
	 * Checking whether the spreadsheet library is available
	 */
	public function process()
	{
		$this->status = \App\Modules\Settings\ModuleManager\Models\Library::checkLibrary('PhpSpreadsheet') ? 0 : 1;
		if ($this->status === 0) {
			$this->description = \App\Runtime\Vtiger_Language_Handler::translate('LBL_MISSING_COMPOSER_LIBRARY', 'Settings:SystemWarnings', 'phpoffice/phpspreadsheet');
		}
	}
}
