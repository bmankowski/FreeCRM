<?php

namespace FreeCRM\Modules\Notification;

/**
 * Notifications parser class
 * @package YetiForce.TextParser
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class TextParser extends \App\TextParser\Base
{

	/** @var string Class name */
	public $name = 'LBL_NOTIFICATIONS';

	/** @var mixed Parser type */
	public $type = 'mail';

	/**
	 * Process
	 * @return string
	 */
	public function process()
	{
		$siteURL = \AppConfig::main('site_URL');
		$html = '';
		$scheduleData = \FreeCRM\Modules\Vtiger\Models\Watchdog::getWatchingModulesSchedule($this->textParser->getParam('userId'), true);
		$modules = $scheduleData['modules'];

		$notificationInstance = \FreeCRM\Modules\Notification\Models\Module::getInstance('Notification');
		$entries = \FreeCRM\Modules\Notification\Models\Module::getEmailSendEntries($this->textParser->getParam('userId'), $modules, $this->textParser->getParam('startDate'), $this->textParser->getParam('endDate'));
		$pattern = "/(?<=href=(\"|'))[^\"']+(?=(\"|'))/";
		foreach ($notificationInstance->getTypes() as $typeId => $type) {
			if (isset($entries[$typeId])) {
				$html .= "<hr><strong>$type</strong><ul>";
				foreach ($entries[$typeId] as $notification) {
					$title = preg_replace_callback(
						$pattern, function ($matches) {
						return \AppConfig::main('site_URL') . $matches[0];
					}, $notification->getTitle());
					$massage = preg_replace_callback(
						$pattern, function ($matches) {
						return \AppConfig::main('site_URL') . $matches[0];
					}, $notification->getMessage());
					$html .= "<li>$title<br>$massage</li>";
				}
				$html .= '</ul><br>';
			}
		}
		if (empty($html)) {
			$html = \LanguageTranslator::translate('LBL_NO_NOTIFICATIONS', 'Notification');
		}
		return $html;
	}
}
