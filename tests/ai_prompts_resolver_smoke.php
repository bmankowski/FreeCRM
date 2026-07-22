<?php
/**
 * Smoke test for AI PromptResolver — run via:
 * docker compose exec -T app php tests/ai_prompts_resolver_smoke.php
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

use App\Ai\Prompt\ActionRegistry;
use App\Ai\Prompt\PromptNotFoundException;
use App\Ai\Prompt\PromptResolver;

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

assertTrue(ActionRegistry::isKnown(ActionRegistry::MAIL_IMPROVE), 'mail.improve is registered');

try {
	$body = PromptResolver::resolve(ActionRegistry::MAIL_IMPROVE, null);
	assertTrue($body !== '', 'resolve system mail.improve returns non-empty');
	assertTrue(str_contains($body, '{{body}}'), 'seed prompt contains {{body}}');
	assertTrue(str_contains($body, '{{subject}}'), 'seed prompt contains {{subject}}');
} catch (PromptNotFoundException $e) {
	assertTrue(false, 'resolve system mail.improve: ' . $e->getMessage());
	$body = '';
}

try {
	PromptResolver::resolve('mail.unknown');
	assertTrue(false, 'unknown action_key should throw');
} catch (PromptNotFoundException $e) {
	assertTrue(true, 'unknown action_key throws');
}

if ($body !== '') {
	try {
		$rendered = PromptResolver::applyPlaceholders($body, [
			'subject' => 'Hello',
			'body' => 'Please confirm.',
		]);
		assertTrue(str_contains($rendered, 'Hello'), 'placeholder subject applied');
		assertTrue(str_contains($rendered, 'Please confirm.'), 'placeholder body applied');
		assertTrue(!str_contains($rendered, '{{'), 'no leftover placeholders');
	} catch (PromptNotFoundException $e) {
		assertTrue(false, 'applyPlaceholders happy path: ' . $e->getMessage());
	}

	try {
		PromptResolver::applyPlaceholders($body, ['subject' => 'only']);
		assertTrue(false, 'missing body placeholder should throw');
	} catch (PromptNotFoundException $e) {
		assertTrue(true, 'missing placeholder throws');
	}
}

$dup = (new \App\Db\Query())
	->from('s_#__ai_prompts')
	->where(['action_key' => ActionRegistry::MAIL_IMPROVE, 'userid' => null])
	->count();
assertTrue((int) $dup === 1, 'exactly one system mail.improve row');

echo $failures === 0 ? "\nALL PASSED\n" : "\nFAILED: $failures\n";
exit($failures === 0 ? 0 : 1);
