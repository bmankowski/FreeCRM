<?php
namespace App\SystemWarnings\SystemRequirements;

/**
 * Privilege File basic class
 * @package YetiForce.SystemWarnings
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ConfReport extends \App\SystemWarnings\Template
{

	protected $status = 2;
	protected $title = 'LBL_CONFIG_REPORT';
	protected $priority = 7;
	protected $linkTitle = 'LBL_CONFIG_REPORT_LINK';

	/**
	 * Checking whether all the configuration parameters are correct
	 */
	public function process()
	{
		$status = 2;
		$permissionsFiles = \App\Modules\Settings\ConfReport\Models\Module::getPermissionsFiles(true);
		if (!empty($permissionsFiles)) {
			$status = 2;
		}
		if ($status) {
			$library = \App\Modules\Settings\ConfReport\Models\Module::getConfigurationLibrary();
			foreach ($library as $key => $value) {
				if ($value['status'] === 'LBL_NO') {
					$status = 2;
				}
			}
		}
		if ($status) {
			$directiveValues = \App\Modules\Settings\ConfReport\Models\Module::getConfigurationValue();
			foreach ($directiveValues as $key => $value) {
				if (isset($value['status']) && $value['status']) {
					$status = 2;
				}
			}
		}
		if ($status) {
			$this->status = 1;
		} else {
			$this->status = 0;
			$this->link = 'index.php?parent=Settings&module=ConfReport&view=Index';
			$this->linkTitle = \App\Runtime\Vtiger_Language_Handler::translate('LBL_CONFIG_REPORT_LINK', 'Settings:SystemWarnings');
			$this->description = \App\Runtime\Vtiger_Language_Handler::translate('LBL_CONFIG_REPORT_DESC', 'Settings:SystemWarnings');
		}
	}
}
