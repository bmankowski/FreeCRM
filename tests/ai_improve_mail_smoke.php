<?php
/**
 * Smoke test for AI improve-mail (config + service validation, no live OpenAI).
 * docker compose exec -T app php tests/ai_improve_mail_smoke.php
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

use App\Ai\Mail\ImproveMailService;
use App\Ai\OpenAi\Client;
use App\Ai\OpenAi\OpenAiException;
use App\Modules\Settings\AiPrompts\Models\ProviderConfig;

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

$config = ProviderConfig::get();
assertTrue($config['provider'] === 'openai', 'provider row is openai');
assertTrue($config['model'] !== '', 'model is non-empty');

$hadKey = $config['has_api_key'];
$savedKey = $config['api_key'];

ProviderConfig::save(null, 'gpt-5-nano', true);
try {
	ProviderConfig::requireConfigured();
	assertTrue(false, 'requireConfigured should throw without key');
} catch (OpenAiException $e) {
	assertTrue($e->getMessage() === 'LBL_AI_API_KEY_MISSING', 'missing key message');
}

try {
	ImproveMailService::improve('Sub', '', null);
	assertTrue(false, 'empty body should throw');
} catch (OpenAiException $e) {
	assertTrue($e->getMessage() === 'LBL_AI_MAIL_BODY_EMPTY', 'empty body message');
}

try {
	ImproveMailService::improve('Sub', '<p>Hello</p>', null);
	assertTrue(false, 'improve without key should throw');
} catch (OpenAiException $e) {
	assertTrue($e->getMessage() === 'LBL_AI_API_KEY_MISSING', 'improve without key');
}

$stripped = \App\Ai\OpenAi\Client::stripCodeFences("```html\n<p>Hi</p>\n```");
assertTrue($stripped === '<p>Hi</p>', 'strip html fences');
assertTrue(\App\Ai\OpenAi\Client::isChatModelId('gpt-5-nano'), 'gpt-5-nano is chat');
assertTrue(!\App\Ai\OpenAi\Client::isChatModelId('text-embedding-3-small'), 'embedding filtered');

$withFooter = '<div class="fc-email-content"><p>Hello</p></div><div class="fc-email-footer"><p>Sig</p></div>';
$extracted = ImproveMailService::extractEmailContent($withFooter);
assertTrue($extracted['hadContentWrapper'] === true, 'detects fc-email-content');
assertTrue(trim($extracted['content']) === '<p>Hello</p>', 'extracts content only');
assertTrue(!str_contains($extracted['content'], 'Sig'), 'content excludes footer text');
$rebuilt = ImproveMailService::replaceEmailContent($withFooter, '<p>Improved</p>', true);
assertTrue(str_contains($rebuilt, '<p>Improved</p>'), 'puts improved content back');
assertTrue(str_contains($rebuilt, 'fc-email-footer') && str_contains($rebuilt, '<p>Sig</p>'), 'keeps footer untouched');

$nestedFooter = '<div class="fc-email-content"><p>Hi</p><div class="fc-email-footer"><p>Nested</p></div></div>';
$nested = ImproveMailService::extractEmailContent($nestedFooter);
assertTrue(!str_contains($nested['content'], 'Nested'), 'strips footer nested inside content');

$inlineFooter = '<p>Message only</p><div class="fc-email-footer"><p>Sig</p></div><div class="fc-email-footer"><p>Unsub</p></div>';
$inline = ImproveMailService::extractEmailContent($inlineFooter);
assertTrue(trim(strip_tags($inline['content'])) === 'Message only', 'inline footers excluded from AI body');
assertTrue(str_contains($inline['detachedFooterHtml'], 'Sig') && str_contains($inline['detachedFooterHtml'], 'Unsub'), 'keeps detached footers');
$inlineRebuilt = ImproveMailService::replaceEmailContent($inlineFooter, '<p>Better</p>', false, $inline['detachedFooterHtml']);
assertTrue(str_contains($inlineRebuilt, '<p>Better</p>') && str_contains($inlineRebuilt, 'Sig'), 'reassembles message + footers');

$legacy = '<p>Hello candidate</p><p><br></p><table><tr><td><img class="user-photo-inline" src="x" alt=""></td><td>Pozdrawiam</td></tr></table><p>Unsub</p>';
$legacyEx = ImproveMailService::extractEmailContent($legacy);
assertTrue(str_contains($legacyEx['content'], 'Hello candidate'), 'legacy keeps message');
assertTrue(!str_contains($legacyEx['content'], 'user-photo-inline'), 'legacy strips signature from AI body');
assertTrue(str_contains($legacyEx['detachedFooterHtml'], 'user-photo-inline'), 'legacy keeps signature for reattach');

$plain = ImproveMailService::extractEmailContent('<p>Only body</p>');
assertTrue($plain['hadContentWrapper'] === false && trim($plain['content']) === '<p>Only body</p>', 'plain body passthrough');
assertTrue($plain['detachedFooterHtml'] === '', 'plain has no detached footer');

if ($hadKey && is_string($savedKey)) {
	ProviderConfig::save($savedKey, $config['model'] ?: 'gpt-5-nano', false);
	echo "OK: restored previous API key\n";
} else {
	ProviderConfig::save(null, $config['model'] ?: 'gpt-5-nano', true);
	echo "OK: left API key empty\n";
}

echo $failures === 0 ? "\nALL PASSED\n" : "\nFAILED: $failures\n";
exit($failures === 0 ? 0 : 1);
