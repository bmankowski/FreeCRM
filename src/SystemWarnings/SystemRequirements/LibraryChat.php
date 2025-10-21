<?php
namespace App\SystemWarnings\SystemRequirements;


/**
 * Privilege File basic class
 * @package YetiForce.SystemWarnings
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class LibraryChat extends \App\SystemWarnings\Template
{

	protected $status = 2;
	protected $title = 'LBL_LIBRARY_CHAT';
	protected $priority = 4;

	/**
	 * Checking whether there is a library AJAXChat
	 */
	public function process()
	{
		$this->status = \App\Modules\Settings\ModuleManager\Models\Library::checkLibrary('AJAXChat') ? 0 : 1;
		if ($this->status === 0) {
			$this->link = 'index.php?module=ModuleManager&parent=Settings&view=List';
			$this->linkTitle = \App\Runtime\Vtiger_Language_Handler::translate('BTN_DOWNLOAD_LIBRARY', 'Settings:SystemWarnings');
			$this->description = \App\Runtime\Vtiger_Language_Handler::translate('LBL_MISSING_LIBRARY', 'Settings:SystemWarnings', \App\Modules\Settings\ModuleManager\Models\Library::TEMP_DIR);
		}
	}
}
