<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Ai\Mail;

use App\Ai\OpenAi\Client;
use App\Ai\OpenAi\OpenAiException;
use App\Ai\OpenAi\RequestContext;
use App\Ai\Prompt\ActionRegistry;
use App\Ai\Prompt\PromptNotFoundException;
use App\Ai\Prompt\PromptResolver;
use App\Modules\Settings\AiPrompts\Models\ProviderConfig;

/**
 * Improve compose-mail HTML via PromptResolver + OpenAI.
 *
 * Only the message body is sent to the model. Footers (`.fc-email-footer`, or
 * legacy signature blocks) stay in the compose HTML and are reattached after.
 */
final class ImproveMailService
{
	private const SYSTEM_INSTRUCTION = 'You improve business email HTML body content only. Do not add a signature or footer. Reply with HTML body only — no markdown code fences, no commentary.';

	/**
	 * @throws OpenAiException
	 * @throws PromptNotFoundException
	 */
	public static function improve(string $subject, string $bodyHtml, ?int $userId = null, ?Client $client = null): string
	{
		$bodyHtml = trim($bodyHtml);
		if ($bodyHtml === '') {
			throw new OpenAiException('LBL_AI_MAIL_BODY_EMPTY');
		}

		$extracted = self::extractEmailContent($bodyHtml);
		$content = trim($extracted['content']);
		if ($content === '' || self::isVisuallyEmpty($content)) {
			throw new OpenAiException('LBL_AI_MAIL_BODY_EMPTY');
		}

		$provider = ProviderConfig::requireConfigured();
		$template = PromptResolver::resolve(ActionRegistry::MAIL_IMPROVE, $userId);
		$userPrompt = PromptResolver::applyPlaceholders($template, [
			'subject' => $subject,
			'body' => $content,
		]);

		$client ??= new Client();
		$improved = trim($client->chatCompletions(
			$provider['api_key'],
			$provider['model'],
			[
				['role' => 'system', 'content' => self::SYSTEM_INSTRUCTION],
				['role' => 'user', 'content' => $userPrompt],
			],
			new RequestContext(ActionRegistry::MAIL_IMPROVE, $userId)
		));
		$improved = trim(self::extractEmailContent($improved)['content']);
		if ($improved === '') {
			throw new OpenAiException('LBL_AI_IMPROVE_FAILED');
		}

		return self::replaceEmailContent(
			$bodyHtml,
			$improved,
			$extracted['hadContentWrapper'],
			$extracted['detachedFooterHtml']
		);
	}

	/**
	 * Message body for the model — never includes footer/signature blocks.
	 *
	 * @return array{content: string, hadContentWrapper: bool, detachedFooterHtml: string}
	 */
	public static function extractEmailContent(string $html): array
	{
		$html = trim($html);
		if ($html === '') {
			return ['content' => '', 'hadContentWrapper' => false, 'detachedFooterHtml' => ''];
		}

		$dom = self::loadFragment($html);
		$root = $dom->getElementById('fc-ai-root');
		if (!$root instanceof \DOMElement) {
			return ['content' => $html, 'hadContentWrapper' => false, 'detachedFooterHtml' => ''];
		}

		$detachedFooterHtml = self::detachFooterElements($root);
		$contentEl = self::findFirstByClass($root, 'fc-email-content');
		if ($contentEl instanceof \DOMElement) {
			self::detachFooterElements($contentEl);

			return [
				'content' => self::innerHtml($contentEl),
				'hadContentWrapper' => true,
				'detachedFooterHtml' => $detachedFooterHtml,
			];
		}

		if ($detachedFooterHtml === '') {
			$detachedFooterHtml = self::detachLegacySignatureTail($root);
		}

		return [
			'content' => self::innerHtml($root),
			'hadContentWrapper' => false,
			'detachedFooterHtml' => $detachedFooterHtml,
		];
	}

	/**
	 * Put improved body back, preserving footer siblings / detached footer HTML.
	 */
	public static function replaceEmailContent(
		string $originalHtml,
		string $improvedContent,
		bool $hadContentWrapper,
		string $detachedFooterHtml = ''
	): string {
		$improvedContent = rtrim($improvedContent);
		if (!$hadContentWrapper) {
			return $improvedContent . ($detachedFooterHtml !== '' ? $detachedFooterHtml : '');
		}

		$dom = self::loadFragment($originalHtml);
		$root = $dom->getElementById('fc-ai-root');
		if (!$root instanceof \DOMElement) {
			return '<div class="fc-email-content">' . $improvedContent . '</div>' . $detachedFooterHtml;
		}

		$contentEl = self::findFirstByClass($root, 'fc-email-content');
		if ($contentEl === null) {
			return '<div class="fc-email-content">' . $improvedContent . '</div>'
				. self::innerHtml($root);
		}

		while ($contentEl->firstChild !== null) {
			$contentEl->removeChild($contentEl->firstChild);
		}
		self::appendHtmlFragment($contentEl, $improvedContent);

		return self::innerHtml($root);
	}

	/**
	 * Remove top-level `.fc-email-footer` nodes under $scope (skip nested ones).
	 */
	private static function detachFooterElements(\DOMElement $scope): string
	{
		$footers = [];
		foreach ($scope->getElementsByTagName('*') as $el) {
			if (!$el instanceof \DOMElement || !self::hasClass($el, 'fc-email-footer')) {
				continue;
			}
			if (self::hasFooterAncestor($el, $scope)) {
				continue;
			}
			$footers[] = $el;
		}
		if ($footers === []) {
			return '';
		}

		$html = '';
		foreach ($footers as $el) {
			$html .= $el->ownerDocument?->saveHTML($el) ?? '';
			$el->parentNode?->removeChild($el);
		}

		return $html;
	}

	private static function hasFooterAncestor(\DOMElement $el, \DOMElement $scope): bool
	{
		$parent = $el->parentNode;
		while ($parent instanceof \DOMElement && !$parent->isSameNode($scope)) {
			if (self::hasClass($parent, 'fc-email-footer')) {
				return true;
			}
			$parent = $parent->parentNode;
		}

		return false;
	}

	/**
	 * Templates that baked signature HTML into content (no fc-email wrappers):
	 * drop from the first signature marker's top-level block through the end.
	 */
	private static function detachLegacySignatureTail(\DOMElement $root): string
	{
		$marker = null;
		foreach ($root->getElementsByTagName('*') as $el) {
			if (!$el instanceof \DOMElement) {
				continue;
			}
			if (self::hasClass($el, 'user-photo-inline') || self::hasClass($el, 'organizationLogo')) {
				$marker = $el;
				break;
			}
		}
		if ($marker === null) {
			return '';
		}

		$top = $marker;
		while ($top->parentNode instanceof \DOMElement && !$top->parentNode->isSameNode($root)) {
			$top = $top->parentNode;
		}
		if (!$top->parentNode?->isSameNode($root)) {
			return '';
		}

		// Include a leading empty spacer paragraph that belongs to standard_user_footer.
		$start = $top;
		$prev = $top->previousSibling;
		while ($prev instanceof \DOMText && trim($prev->textContent ?? '') === '') {
			$prev = $prev->previousSibling;
		}
		if ($prev instanceof \DOMElement && strtolower($prev->tagName) === 'p' && self::isVisuallyEmpty(self::innerHtml($prev))) {
			$start = $prev;
		}

		$html = '';
		$node = $start;
		while ($node !== null) {
			$next = $node->nextSibling;
			$html .= $root->ownerDocument?->saveHTML($node) ?? '';
			$root->removeChild($node);
			$node = $next;
		}

		return $html;
	}

	private static function loadFragment(string $html): \DOMDocument
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(true);
		$dom->loadHTML(
			'<?xml encoding="UTF-8"><div id="fc-ai-root">' . $html . '</div>',
			LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		return $dom;
	}

	private static function appendHtmlFragment(\DOMElement $parent, string $html): void
	{
		$html = trim($html);
		if ($html === '') {
			return;
		}
		$tmp = self::loadFragment($html);
		$tmpRoot = $tmp->getElementById('fc-ai-root');
		if (!$tmpRoot instanceof \DOMElement) {
			$parent->appendChild($parent->ownerDocument->createTextNode($html));

			return;
		}
		foreach (iterator_to_array($tmpRoot->childNodes) as $child) {
			$parent->appendChild($parent->ownerDocument->importNode($child, true));
		}
	}

	private static function isVisuallyEmpty(string $html): bool
	{
		$text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = str_replace("\xc2\xa0", ' ', $text);

		return trim($text) === '';
	}

	private static function findFirstByClass(\DOMElement $root, string $className): ?\DOMElement
	{
		foreach ($root->getElementsByTagName('*') as $el) {
			if ($el instanceof \DOMElement && self::hasClass($el, $className)) {
				return $el;
			}
		}

		return null;
	}

	private static function hasClass(\DOMElement $el, string $className): bool
	{
		$classes = preg_split('/\s+/', trim($el->getAttribute('class'))) ?: [];

		return in_array($className, $classes, true);
	}

	private static function innerHtml(\DOMElement $el): string
	{
		$html = '';
		foreach ($el->childNodes as $child) {
			$html .= $el->ownerDocument?->saveHTML($child) ?? '';
		}

		return $html;
	}
}
