<?php
namespace App\Debug;

/**
 * Debugger basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
use DebugBar;
use DebugBar\DataCollector;
use Yii;
use App\AppConfig;

class Debugger
{

	protected static $debugBar;

	/**
	 * Initiating debugging console
	 * @return \App\DebugBar\Debugger
	 */
	static public function initConsole()
	{
		if (!class_exists('DebugBar\\DebugBar')) {
			// DebugBar library not installed; skip console initialization
			return null;
		}
		$debugbar = new DebugBar\DebugBar();
		$debugbar->addCollector(new DataCollector\PhpInfoCollector());
		$debugbar->addCollector(new DataCollector\RequestDataCollector());
		$debugbar->addCollector(new DataCollector\TimeDataCollector());
		$debugbar->addCollector(new DataCollector\MemoryCollector());
		if (\App\Core\AppConfig::debug('LOG_TO_CONSOLE')) {
			$debugbar->addCollector(new DebugBarLogs());
		}
		$debugbar->addCollector(new DataCollector\ExceptionsCollector());
		
		// Configure JavascriptRenderer
		$renderer = $debugbar->getJavascriptRenderer();
		// Exclude vendor assets (jQuery, fontawesome already in project)
		$renderer->setIncludeVendors(false);
		// Set base URL for DebugBar assets
		// Check public/libraries/debugbar first (preferred for web-accessible assets)
		$publicLibrariesBasePath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'debugbar';
		$librariesBasePath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'debugbar';
		$vendorBasePath = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'php-debugbar' . DIRECTORY_SEPARATOR . 'php-debugbar' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'DebugBar' . DIRECTORY_SEPARATOR . 'Resources';
		
		if (file_exists($publicLibrariesBasePath . DIRECTORY_SEPARATOR . 'debugbar.css')) {
			// Use public/libraries/debugbar path (preferred for web-accessible assets)
			// If DocumentRoot points to /public, URL should be /libraries/debugbar (without /public/)
			$renderer->setBasePath($publicLibrariesBasePath);
			$renderer->setBaseUrl('/libraries/debugbar');
		} elseif (file_exists($librariesBasePath . DIRECTORY_SEPARATOR . 'debugbar.css')) {
			// Use libraries/debugbar path if resources were copied
			$renderer->setBasePath($librariesBasePath);
			$renderer->setBaseUrl('/libraries/debugbar');
		} elseif (file_exists($vendorBasePath)) {
			// Fallback to vendor path (may not be accessible via HTTP)
			$renderer->setBasePath($vendorBasePath);
			$renderer->setBaseUrl('/vendor/php-debugbar/php-debugbar/src/DebugBar/Resources');
		}
		
		return static::$debugBar = $debugbar;
	}

	/**
	 * Get Debugger instance
	 * @return \App\DebugBar\Debugger
	 */
	static public function getDebugBar()
	{
		return static::$debugBar;
	}

	/**
	 * Checking is active debugging
	 * @return bool
	 */
	static public function isDebugBar()
	{
		return isset(static::$debugBar);
	}

	public static function addLogs($message, $level, $traces)
	{
		if (isset(static::$debugBar['logs'])) {
			static::$debugBar['logs']->addMessage($message, $level, $traces);
		}
	}

	/**
	 * Initiating debugging
	 */
	public static function init()
	{
		if (\App\Core\AppConfig::debug('DISPLAY_DEBUG_CONSOLE') && static::checkIP()) {
			static::initConsole();
		}
		$targets = [];
		$normalizeLevels = function ($levels) {
			if ($levels === false || $levels === null) {
				return null;
			}
			$allowed = ['error', 'warning', 'info', 'trace', 'profile'];
			if (is_string($levels)) {
				$levels = array_map('trim', explode(',', $levels));
			}
			if (!is_array($levels)) {
				return null;
			}
			$mapped = [];
			foreach ($levels as $level) {
				$level = strtolower($level);
				if ($level === 'debug') {
					$level = 'trace';
				}
				if (in_array($level, $allowed, true)) {
					$mapped[] = $level;
				}
			}
			return $mapped ?: null;
		};
		if (\App\Core\AppConfig::debug('LOG_TO_FILE')) {
			$levels = $normalizeLevels(\App\Core\AppConfig::debug('LOG_LEVELS'));
			$target = [
				'class' => 'App\Log\FileTarget'
			];
			if ($levels !== null) {
				$target['levels'] = $levels;
			}
			$targets['file'] = $target;
		}
		
		// Add SQL log target for SELECT queries
		if (\App\Core\AppConfig::debug('LOG_TO_FILE')) {
			$sqlTarget = [
				'class' => 'App\Log\SqlLogTarget',
				'levels' => ['profile'], // Only profile level for SQL queries
				'categories' => ['SQL_SELECT'] // Only SELECT queries
			];
			$targets['sql'] = $sqlTarget;
		}
		if (\App\Core\AppConfig::debug('LOG_TO_PROFILE')) {
			$levels = $normalizeLevels(\App\Core\AppConfig::debug('LOG_LEVELS'));
			$target = [
				'class' => 'App\Log\Profiling'
			];
			if ($levels !== null) {
				$target['levels'] = $levels;
			}
			$targets['profiling'] = $target;
		}
		Yii::createObject([
			'class' => 'yii\log\Dispatcher',
			'traceLevel' => \App\Core\AppConfig::debug('LOG_TRACE_LEVEL'),
			'targets' => $targets
		]);
	}

	/**
	 * Checking user IP
	 * @return boolean
	 */
	public static function checkIP()
	{
		$ips = \App\Core\AppConfig::debug('DEBUG_CONSOLE_ALLOWED_IPS');
		if ($ips === false) {
			return true;
		}
		if (is_array($ips) && in_array(\App\Utils\RequestUtil::getRemoteIP(true), $ips)) {
			return true;
		} elseif (is_string($ips) && \App\Utils\RequestUtil::getRemoteIP(true) === $ips) {
			return true;
		}
		return false;
	}

	/**
	 * Generates a backtrace
	 * @param int $minLevel
	 * @param int $maxLevel
	 * @param string $sep
	 * @return string
	 */
	public static function getBacktrace($minLevel = 1, $maxLevel = 0, $sep = '#')
	{
		$trace = '';
		foreach (debug_backtrace() as $k => $v) {
			if ($k < $minLevel) {
				continue;
			}
			$l = $k - $minLevel;
			$args = '';
			if (isset($v['args'])) {
				foreach ($v['args'] as &$arg) {
					if (!is_array($arg) && !is_object($arg) && !is_resource($arg)) {
						$args .= var_export($arg, true);
					} elseif (is_array($arg)) {
						$args .= '[';
						foreach ($arg as &$a) {
							$val = $a;
							if (is_array($a) || is_object($a) || is_resource($a)) {
								$val = gettype($a);
								if (is_object($a)) {
									$val .= '(' . get_class($a) . ')';
								}
							}
							$args .= $val . ',';
						}
						$args = rtrim($args, ',') . ']';
					}
					$args .= ',';
				}
				$args = rtrim($args, ',');
			}
			$file = isset($v['file']) ? $v['file'] : 'unknown';
			$line = isset($v['line']) ? $v['line'] : 'unknown';
			$trace .= "$sep$l {$file} ({$line})  >>  " . (isset($v['class']) ? $v['class'] . '->' : '') . "{$v['function']}($args)" . PHP_EOL;
			if ($maxLevel !== 0 && $l >= $maxLevel) {
				break;
			}
		}
		return rtrim(str_replace(ROOT_DIRECTORY . DIRECTORY_SEPARATOR, '', $trace), PHP_EOL);
	}
}
