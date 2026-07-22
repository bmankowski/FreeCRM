<?php
/**
 * Smoke test for AI request logging (redaction + flag, no live OpenAI).
 * docker compose exec -T app php tests/ai_request_logging_smoke.php
 */

declare(strict_types=1);

define('ROOT_DIRECTORY', dirname(__DIR__));
define('REQUEST_MODE', 'TEST');

require_once ROOT_DIRECTORY . '/vendor/autoload.php';
require_once ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require_once ROOT_DIRECTORY . '/config/api.php';
require_once ROOT_DIRECTORY . '/config/config.php';
\App\Core\AppConfig::init($API_CONFIG);
\App\Core\Loader::register();
\App\EntryPoint\WebUI::initialize();

use App\Ai\OpenAi\AiRequestLogger;
use App\Ai\OpenAi\RequestContext;

$failures = 0;

function assertTrue(bool $cond, string $msg): void
{
	global $failures;
	if (!$cond) {
		echo "FAIL: $msg\n";
		++$failures;
	} else {
		echo "OK: $msg\n";
	}
}

$sample = 'Hello <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=="> sk-proj-ABCDEFGHIJKLMNOPQRSTUV';
$redacted = AiRequestLogger::redactForLog($sample);
assertTrue(str_contains($redacted, '[data-uri omitted '), 'redacts data URI');
assertTrue(!str_contains($redacted, 'base64,iVBORw0'), 'removes base64 payload');
assertTrue(str_contains($redacted, 'sk-***'), 'redacts api key fragment');
assertTrue(str_contains($redacted, 'Hello'), 'keeps surrounding text');

$ctx = new RequestContext('mail.improve', 7);
assertTrue($ctx->action === 'mail.improve' && $ctx->userId === 7, 'RequestContext stores action/user');

try {
	new RequestContext('  ');
	assertTrue(false, 'empty action should throw');
} catch (\InvalidArgumentException) {
	assertTrue(true, 'empty action throws');
}

assertTrue(AiRequestLogger::statusFromHttpResult(28, 0, true) === 'timeout', 'errno 28 → timeout');
assertTrue(AiRequestLogger::statusFromHttpResult(7, 0, true) === 'transport', 'other errno → transport');
assertTrue(AiRequestLogger::statusFromHttpResult(0, 401, true) === 'http_4xx', '401 → http_4xx');
assertTrue(AiRequestLogger::statusFromHttpResult(0, 200, false) === 'invalid_json', 'bad json');

$tmp = sys_get_temp_dir() . '/freecrm-ai-log-smoke-' . bin2hex(random_bytes(4)) . '.log';
AiRequestLogger::$pathOverride = $tmp;
AiRequestLogger::$enabledOverride = false;
AiRequestLogger::writeExchange([
	'id' => 'disabled',
	'action' => 'mail.improve',
	'userId' => 1,
	'model' => 'gpt-test',
	'endpoint' => 'chat.completions',
	'requestBytes' => 10,
	'status' => 'ok',
	'durationMs' => 1.5,
	'http' => 200,
	'errno' => 0,
	'responseBytes' => 5,
	'content' => 'hi',
]);
assertTrue(!is_file($tmp), 'flag off writes nothing');

AiRequestLogger::$enabledOverride = true;
AiRequestLogger::writeExchange([
	'phase' => 'start',
	'id' => 'abc123',
	'action' => 'mail.improve',
	'userId' => 1,
	'model' => 'gpt-test',
	'endpoint' => 'chat.completions',
	'requestBytes' => 42,
	'messages' => [
		['role' => 'system', 'content' => 'sys'],
		['role' => 'user', 'content' => 'body data:image/gif;base64,AAAA'],
	],
]);
assertTrue(is_file($tmp), 'start phase creates log file before HTTP');
$startedLog = (string) file_get_contents($tmp);
assertTrue(str_contains($startedLog, '=== ai.request id=abc123'), 'writes request header');
assertTrue(str_contains($startedLog, 'action=mail.improve'), 'writes action');
assertTrue(str_contains($startedLog, '[data-uri omitted '), 'redacts message data URI in file');
assertTrue(str_contains($startedLog, 'status=started'), 'start status before HTTP');
assertTrue(str_contains($startedLog, '=== ai.waiting id=abc123'), 'waiting marker before HTTP');
assertTrue(!str_contains($startedLog, '=== ai.end id=abc123'), 'end marker only after result');

AiRequestLogger::writeExchange([
	'phase' => 'result',
	'id' => 'abc123',
	'status' => 'ok',
	'durationMs' => 12.34,
	'http' => 200,
	'errno' => 0,
	'responseBytes' => 9,
	'content' => '<p>out</p>',
	'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3],
]);
$written = (string) file_get_contents($tmp);
assertTrue(str_contains($written, '=== ai.result id=abc123'), 'writes result header');
assertTrue(str_contains($written, 'status=ok'), 'writes status');
assertTrue(str_contains($written, 'usage: prompt_tokens=1'), 'writes usage');
assertTrue(str_contains($written, '=== ai.end id=abc123'), 'writes end marker');

@unlink($tmp);
AiRequestLogger::$pathOverride = null;
AiRequestLogger::$enabledOverride = null;

if ($failures > 0) {
	echo "\n{$failures} failure(s)\n";
	exit(1);
}
echo "\nAll checks passed.\n";
