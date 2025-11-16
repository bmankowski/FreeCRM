<?php
namespace App;

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
		if (\App\AppConfig::debug('LOG_TO_CONSOLE')) {
			$debugbar->addCollector(new Debug\DebugBarLogs());
		}
		$debugbar->addCollector(new DataCollector\ExceptionsCollector());
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
		if (\App\AppConfig::debug('DISPLAY_DEBUG_CONSOLE') && static::checkIP()) {
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
		if (\App\AppConfig::debug('LOG_TO_FILE')) {
			$levels = $normalizeLevels(\App\AppConfig::debug('LOG_LEVELS'));
			$target = [
				'class' => 'App\Log\FileTarget'
			];
			if ($levels !== null) {
				$target['levels'] = $levels;
			}
			$targets['file'] = $target;
		}
		
		// Add SQL log target for SELECT queries
		if (\App\AppConfig::debug('LOG_TO_FILE')) {
			$sqlTarget = [
				'class' => 'App\Log\SqlLogTarget',
				'levels' => ['profile'], // Only profile level for SQL queries
				'categories' => ['SQL_SELECT'] // Only SELECT queries
			];
			$targets['sql'] = $sqlTarget;
		}
		if (\App\AppConfig::debug('LOG_TO_PROFILE')) {
			$levels = $normalizeLevels(\App\AppConfig::debug('LOG_LEVELS'));
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
			'traceLevel' => \App\AppConfig::debug('LOG_TRACE_LEVEL'),
			'targets' => $targets
		]);
	}

	/**
	 * Checking user IP
	 * @return boolean
	 */
	public static function checkIP()
	{
		$ips = \App\AppConfig::debug('DEBUG_CONSOLE_ALLOWED_IPS');
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
