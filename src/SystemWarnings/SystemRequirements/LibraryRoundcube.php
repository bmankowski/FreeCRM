<?php
namespace App\SystemWarnings\SystemRequirements;

use App\Modules\Settings\ModuleManager\Models\Library as Settings_ModuleManager_Library_Model;

/**
 * Privilege File basic class
 * @package YetiForce.SystemWarnings
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class LibraryRoundcube extends \App\SystemWarnings\Template
{

	protected $status = 2;
	protected $title = 'LBL_LIBRARY_ROUNDCUBE';
	protected $priority = 4;

	/**
	 * Checking whether there is a library roundcube
	 */
	public function process()
	{
		$this->status = Settings_ModuleManager_Library_Model::checkLibrary('roundcube') ? 0 : 1;
		if ($this->status === 0) {
			$this->link = 'index.php?module=ModuleManager&parent=Settings&view=List';
			$this->linkTitle = \App\Runtime\Vtiger_Language_Handler::translate('BTN_DOWNLOAD_LIBRARY', 'Settings:SystemWarnings');
			$this->description = \App\Runtime\Vtiger_Language_Handler::translate('LBL_MISSING_LIBRARY', 'Settings:SystemWarnings', Settings_ModuleManager_Library_Model::TEMP_DIR);
		}
	}
}
