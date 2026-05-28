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

namespace App\Modules\RecruitmentApplication\Services\CvImport;

final class PhoneNormalizer
{
	public static function normalize(?string $phoneNumber): string
	{
		if ($phoneNumber === null || $phoneNumber === '') {
			return '';
		}
		$phoneNumber = str_replace('-', '', $phoneNumber);
		$length = strlen($phoneNumber);
		if ($length === 12 && str_starts_with($phoneNumber, '+')) {
			return $phoneNumber;
		}
		if ($length === 13 && str_starts_with($phoneNumber, '00')) {
			return '+' . substr($phoneNumber, 2, 11);
		}
		if ($length === 13 && str_starts_with($phoneNumber, '+')) {
			return $phoneNumber;
		}
		if ($length === 14 && str_starts_with($phoneNumber, '00')) {
			return '+' . substr($phoneNumber, 2, 12);
		}
		if ($length === 11 && str_starts_with($phoneNumber, '48')) {
			return '+' . $phoneNumber;
		}
		if ($length === 9) {
			return '+48' . $phoneNumber;
		}
		return '';
	}

	public static function isValidE164(string $phone): bool
	{
		if ($phone === '') {
			return false;
		}
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			return $phoneUtil->isValidNumber($phoneUtil->parse($phone));
		} catch (\libphonenumber\NumberParseException $e) {
			return false;
		}
	}
}
