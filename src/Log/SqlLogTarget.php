<?php namespace App\Log;

use Yii;
use yii\base\InvalidConfigException;

/**
 * SqlLogTarget records SQL log messages in a separate file.
 * 
 * This target specifically handles SQL SELECT queries and logs them to cache/logs/sql.log
 * instead of the main system.log file.
 *
 * @author FreeCRM
 * @since 1.0
 */
class SqlLogTarget extends \yii\log\FileTarget
{
	/**
	 * @var bool whether log files should be rotated when they reach a certain [[maxFileSize|maximum size]].
	 * Log rotation is enabled by default. This property allows you to disable it, when you have configured
	 * an external tools for log rotation on your server.
	 * @since 2.0.3
	 */
	public $enableRotation = false;

	/**
	 * @var array list of the PHP predefined variables that should be logged in a message.
	 * Note that a variable must be accessible via `$GLOBALS`. Otherwise it won't be logged.
	 */
	public $logVars = [];

	/**
	 * Initializes the route.
	 * This method will initialize the [[logFile]] property to make sure it refers to a valid SQL log file.
	 */
	public function init()
	{
		if ($this->logFile === null) {
			$this->logFile = ROOT_DIRECTORY . '/cache/logs/sql.log';
		} else {
			$this->logFile = Yii::getAlias($this->logFile);
		}
		if ($this->maxLogFiles < 1) {
			$this->maxLogFiles = 1;
		}
		if ($this->maxFileSize < 1) {
			$this->maxFileSize = 1;
		}
	}

	/**
	 * Writes log messages to a file.
	 * @throws InvalidConfigException if unable to open the log file for writing
	 */
	public function export()
	{
		$text = implode("\n", array_map([$this, 'formatMessage'], $this->messages));
		if (($fp = @fopen($this->logFile, 'a')) === false) {
			throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
		}
		@flock($fp, LOCK_EX);
		if ($this->enableRotation) {
			// clear stat cache to ensure getting the real current file size and not a cached one
			// this may result in rotating twice when cached file size is used on subsequent calls
			clearstatcache();
		}
		if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
			$this->rotateFiles();
			@flock($fp, LOCK_UN);
			@fclose($fp);
			$writeResult = @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
			if ($writeResult === false) {
				$error = error_get_last();
				throw new InvalidConfigException("Unable to export log through file!: {$error['message']}");
			}
			$textSize = strlen($text);
			if ($writeResult < $textSize) {
				throw new InvalidConfigException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
			}
		} else {
			$writeResult = @fwrite($fp, $text);
			if ($writeResult === false) {
				$error = error_get_last();
				throw new InvalidConfigException("Unable to export log through file!: {$error['message']}");
			}
			$textSize = strlen($text);
			if ($writeResult < $textSize) {
				throw new InvalidConfigException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
			}
			@flock($fp, LOCK_UN);
			@fclose($fp);
		}
		if ($this->fileMode !== null) {
			@chmod($this->logFile, $this->fileMode);
		}
	}

	/**
	 * Formats a log message for display as a string.
	 * @param array $message the log message to be formatted.
	 * The message structure follows that in [[Logger::messages]].
	 * @return string the formatted message
	 */
	public function formatMessage($message)
	{
		list($text, $level, $category, $timestamp, $traces) = $message;
		$level = \yii\log\Logger::getLevelName($level);
		if (!is_string($text)) {
			// exceptions may not be serializable if in the call stack somewhere is a Closure
			if ($text instanceof \Throwable || $text instanceof \Exception) {
				$text = (string) $text;
			} else {
				$text = \yii\Helpers\VarDumper::export($text);
			}
		}
		$traces = '';
		if (!empty($traces)) {
			$traces = "\n" . $traces;
		}
		return date('Y-m-d H:i:s', (int) $timestamp) . " [$level] [$category] $text" . $traces;
	}
}
