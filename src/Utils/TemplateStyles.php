<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

namespace App\Utils;

/**
 * Shared visual language for PDF and email templates.
 */
class TemplateStyles
{
	const CSS_PUBLIC_PATH = 'layouts/basic/resources/FreeCRMTemplate.css';

	protected static $classStyles = [
		'fc-doc' => 'color:#1f2937;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.55;',
		'container' => 'width:100%;',
		'row' => 'clear:both;display:table;table-layout:fixed;width:100%;',
		'col-4' => 'float:left;min-height:1px;padding-left:8px;padding-right:8px;width:33.3333%;',
		'col-6' => 'float:left;min-height:1px;padding-left:8px;padding-right:8px;width:50%;',
		'col-8' => 'float:left;min-height:1px;padding-left:8px;padding-right:8px;width:66.6667%;',
		'col-12' => 'float:left;min-height:1px;padding-left:8px;padding-right:8px;width:100%;',
		'w-100' => 'width:100%;',
		'h1' => 'color:#111827;font-size:26px;font-weight:700;letter-spacing:-0.02em;line-height:1.18;margin:0 0 16px;',
		'h2' => 'color:#1f2937;font-size:19px;font-weight:700;line-height:1.25;margin:0 0 12px;',
		'h3' => 'color:#1f2937;font-size:16px;font-weight:700;line-height:1.3;margin:0 0 10px;',
		'fc-title' => 'color:#111827;font-size:26px;font-weight:700;letter-spacing:-0.02em;line-height:1.18;margin:0 0 16px;',
		'fc-subtitle' => 'color:#1f2937;font-size:19px;font-weight:700;line-height:1.25;margin:0 0 12px;',
		'fc-section-title' => 'color:#1f2937;font-size:16px;font-weight:700;line-height:1.3;margin:0 0 10px;',
		'small' => 'font-size:10px;',
		'text-left' => 'text-align:left;',
		'text-center' => 'text-align:center;',
		'text-right' => 'text-align:right;',
		'text-muted' => 'color:#6c757d;',
		'font-bold' => 'font-weight:700;',
		'mt-1' => 'margin-top:4px;',
		'mt-2' => 'margin-top:8px;',
		'mt-3' => 'margin-top:16px;',
		'mb-1' => 'margin-bottom:4px;',
		'mb-2' => 'margin-bottom:8px;',
		'mb-3' => 'margin-bottom:16px;',
		'p-1' => 'padding:4px;',
		'p-2' => 'padding:8px;',
		'p-3' => 'padding:16px;',
		'border' => 'border:1px solid #d9e2ec;',
		'rounded' => 'border-radius:4px;',
		'bg-light' => 'background-color:#f8f9fa;',
		'fc-header' => 'margin-bottom:16px;',
		'fc-footer' => 'margin-bottom:16px;',
		'fc-section' => 'margin-bottom:16px;',
		'card' => 'background-color:#fff;border:1px solid #d9e2ec;border-radius:6px;margin-bottom:16px;',
		'card-header' => 'background-color:#f3f6f9;border-bottom:1px solid #d9e2ec;font-weight:700;padding:10px 12px;',
		'card-body' => 'padding:12px;',
		'table' => 'border:0;border-collapse:collapse;border-spacing:0;color:#1f2937;font-size:12px;line-height:1.45;margin:12px 0 18px;width:100%;',
		'table-sm' => '',
		'table-bordered' => '',
		'table-striped' => '',
		'badge' => 'background-color:#6c757d;border-radius:3px;color:#fff;display:inline-block;font-size:10px;font-weight:700;line-height:1;padding:4px 6px;vertical-align:baseline;',
		'badge-success' => 'background-color:#28a745;',
		'badge-warning' => 'background-color:#ffc107;color:#222;',
		'badge-danger' => 'background-color:#dc3545;',
		'alert' => 'background-color:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;margin-bottom:16px;padding:10px 12px;',
		'alert-info' => 'background-color:#eef7ff;border-color:#b8daff;',
		'alert-warning' => 'background-color:#fff8e5;border-color:#ffe8a1;',
		'fc-label' => 'color:#6c757d;font-weight:700;',
		'fc-value' => 'color:#222;',
		'signature' => 'margin-top:32px;padding-top:16px;',
		'signature-line' => 'border-top:1px solid #555;display:inline-block;min-width:180px;padding-top:6px;text-align:center;',
		'img-logo' => 'display:inline-block;max-height:70px;max-width:180px;',
	];

	protected static $tagStyles = [
		'h1' => 'color:#111827;font-size:26px;font-weight:700;letter-spacing:-0.02em;line-height:1.18;margin:0 0 16px;',
		'h2' => 'color:#1f2937;font-size:19px;font-weight:700;line-height:1.25;margin:0 0 12px;',
		'h3' => 'color:#1f2937;font-size:16px;font-weight:700;line-height:1.3;margin:0 0 10px;',
		'h4' => 'color:#1f2937;font-size:13px;font-weight:700;line-height:1.35;margin:0 0 8px;',
		'h5' => 'color:#1f2937;font-size:12px;font-weight:700;line-height:1.35;margin:0 0 8px;',
		'h6' => 'color:#1f2937;font-size:12px;font-weight:700;line-height:1.35;margin:0 0 8px;',
		'p' => 'margin:0 0 10px;',
		'ul' => 'margin:0 0 10px 18px;padding:0;',
		'ol' => 'margin:0 0 10px 18px;padding:0;',
		'li' => 'margin:0 0 4px;',
		'blockquote' => 'background-color:#f8fafc;border-left:3px solid #cbd5e1;color:#475569;margin:0 0 12px;padding:8px 12px;',
		'hr' => 'border:0;border-top:1px solid #e5e7eb;margin:16px 0;',
		'a' => 'color:#0b5cab;text-decoration:none;',
		'small' => 'font-size:10px;',
		'strong' => 'font-weight:700;',
		'table' => 'border:0;border-collapse:collapse;border-spacing:0;color:#1f2937;font-size:12px;line-height:1.45;margin:12px 0 18px;width:100%;',
		'caption' => 'color:#6b7280;font-size:11px;padding:0 0 8px;text-align:left;',
		'th' => 'background-color:#eef3f8;border:1px solid #d7dee8;color:#1f2937;font-weight:700;padding:9px 10px;text-align:left;vertical-align:top;',
		'td' => 'border:1px solid #d7dee8;padding:9px 10px;vertical-align:top;',
		'img' => 'max-width:100%;',
	];

	public static function getCssPath()
	{
		return ROOT_DIRECTORY . '/public/' . self::CSS_PUBLIC_PATH;
	}

	public static function getCss()
	{
		$path = self::getCssPath();
		return is_file($path) ? (string) file_get_contents($path) : '';
	}

	public static function getPublicPath()
	{
		return self::CSS_PUBLIC_PATH;
	}

	public static function inlineEmailCss($html)
	{
		if (!is_string($html) || $html === '' || !self::shouldInlineEmailCss($html)) {
			return $html;
		}
		$internalErrors = libxml_use_internal_errors(true);
		$document = new \DOMDocument('1.0', 'UTF-8');
		$wrappedHtml = '<!doctype html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
		if (!$document->loadHTML($wrappedHtml)) {
			libxml_clear_errors();
			libxml_use_internal_errors($internalErrors);
			return $html;
		}
		$body = $document->getElementsByTagName('body')->item(0);
		if (!$body) {
			libxml_clear_errors();
			libxml_use_internal_errors($internalErrors);
			return $html;
		}
		self::inlineNode($body, true);
		$result = '';
		foreach ($body->childNodes as $child) {
			$result .= $document->saveHTML($child);
		}
		libxml_clear_errors();
		libxml_use_internal_errors($internalErrors);
		return $result;
	}

	protected static function containsTemplateClass($html)
	{
		foreach (array_keys(self::$classStyles) as $className) {
			if (strpos($html, $className) !== false) {
				return true;
			}
		}
		return false;
	}

	protected static function shouldInlineEmailCss($html)
	{
		if (self::containsTemplateClass($html)) {
			return true;
		}
		return (bool) preg_match('/<(table|thead|tbody|tr|th|td|caption|h[1-6]|ul|ol|blockquote|hr)\b/i', $html);
	}

	protected static function inlineNode(\DOMNode $node, $insideTemplate)
	{
		if ($node instanceof \DOMElement) {
			$classAttribute = $node->getAttribute('class');
			$classes = preg_split('/\s+/', trim($classAttribute));
			$insideTemplate = $insideTemplate || in_array('fc-doc', $classes, true);
			$styles = '';
			$tagName = strtolower($node->tagName);
			if ($insideTemplate && isset(self::$tagStyles[$tagName]) && !($tagName === 'table' && in_array('table', $classes, true))) {
				$styles .= self::$tagStyles[$tagName];
			}
			foreach ($classes as $className) {
				if (isset(self::$classStyles[$className])) {
					$styles .= self::$classStyles[$className];
				}
			}
			if ($node->tagName === 'td' || $node->tagName === 'th') {
				$styles .= self::getTableCellStyles($node, !$insideTemplate);
			}
			if ($styles !== '') {
				$existingStyle = $node->getAttribute('style');
				$node->setAttribute('style', $styles . $existingStyle);
			}
		}
		foreach (iterator_to_array($node->childNodes) as $child) {
			self::inlineNode($child, $insideTemplate);
		}
	}

	protected static function getTableCellStyles(\DOMElement $node, $includeBase)
	{
		$parent = $node->parentNode;
		while ($parent && $parent instanceof \DOMElement && strtolower($parent->tagName) !== 'table') {
			$parent = $parent->parentNode;
		}
		if (!$parent instanceof \DOMElement) {
			return '';
		}
		$tableClasses = preg_split('/\s+/', trim($parent->getAttribute('class')));
		$styles = '';
		if ($includeBase && in_array('table', $tableClasses, true)) {
			$styles .= strtolower($node->tagName) === 'th'
				? 'background-color:#eef3f8;border:1px solid #d7dee8;color:#1f2937;font-weight:700;padding:9px 10px;text-align:left;vertical-align:top;'
				: 'border:1px solid #d7dee8;padding:9px 10px;vertical-align:top;';
		}
		if (in_array('table-bordered', $tableClasses, true)) {
			$styles .= 'border:1px solid #d7dee8;';
		}
		if (in_array('table-sm', $tableClasses, true)) {
			$styles .= 'padding:4px 6px;';
		}
		return $styles;
	}
}
