<?php
namespace App\SystemWarnings\YetiForce;

/**
 * Privilege File basic class
 * @package YetiForce.SystemWarnings
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Stats extends \App\SystemWarnings\Template
{

	protected $title = 'LBL_STATS';
	protected $priority = 8;
	protected $tpl = true;


	// static $url = 'https://api.yetiforce.com/stats';
	static $url = 'http://localhost/stats';
	

	/**
	 * Checking whether all the configuration parameters are correct
	 */
	public function process()
	{
		$this->status = 1;
		return;
		if (file_exists('cache/' . $this->getKey()) || \App\AppConfig::main('systemMode') === 'demo') {
			$this->status = 1;
		} else {
			$this->status = 0;
		}
	}

	/**
	 * Get unique key
	 * @return type
	 */
	public function getKey()
	{
		return sha1('Stats' . \App\AppConfig::main('site_URL') . \App\Version::get());
	}

	/**
	 * Update ignoring status
	 * @param int $params
	 * @return boolean
	 */
	public function update($params)
	{
		if (gethostbyname('yetiforce.com') === 'yetiforce.com') {
			\App\Log::warning('ERR_NO_INTERNET_CONNECTION');
			return 'ERR_NO_INTERNET_CONNECTION';
		}
		$result = false;
		$message = \App\Runtime\Vtiger_Language_Handler::translate('LBL_DATA_SAVE_FAIL', 'Settings::SystemWarnings');
		try {
			$request = \Requests::POST(self::$url, [], array_merge($params, [
					'key' => sha1(\App\AppConfig::main('site_URL') . ROOT_DIRECTORY),
					'version' => \App\Version::get(),
					'language' => \App\Runtime\Vtiger_Language_Handler::getLanguage(),
					'timezone' => date_default_timezone_get(),
					]), ['useragent' => 'YetiForceCRM']);
			if ($request->body === 'OK') {
				file_put_contents('cache/' . $this->getKey(), 'Stats');
				$result = true;
				$message = \App\Runtime\Vtiger_Language_Handler::translate('LBL_DATA_SAVE_OK', 'Settings::SystemWarnings');
			}
		} catch (\Exception $exc) {
			\App\Log::warning($exc->getMessage());
		}
		return ['result' => $result, 'message' => $message];
	}
}
