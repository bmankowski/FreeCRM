<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Candidates\Services;

final class CvTextNormalizer
{
	/** pdftotext emits these PUA glyphs for Word/Symbol/Wingdings list bullets. */
	private const PUA_BULLETS = '/[\x{F076}\x{F0A7}\x{F0B7}\x{F0D8}]/u';

	public static function fromExtractedDocument(string $text): string
	{
		return self::normalizeStoredText($text);
	}

	public static function normalizeStoredText(string $text): string
	{
		$text = preg_replace(self::PUA_BULLETS, ' ', $text);
		$text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);
		$text = self::collapseExcessiveBlankLines($text);

		return trim($text);
	}

	private static function collapseExcessiveBlankLines(string $text): string
	{
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);

		return (string) preg_replace('/(?:\n[ \t]*){3,}/', "\n", $text);
	}
}
