<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Canonical configuration describing duplicate-detection sets for ImportManager.
 * Each module entry contains:
 *  - requiredSets: arrays of field names that must be mapped to run the import
 *  - optionalSets: extra sets that can be toggled per batch
 *  - mergeKeys: list of fields used when merging multi-value data
 */

declare(strict_types=1);

return [
	'Contacts' => [
		'requiredSets' => [
			['email'],
			['lastname', 'vat_id'],
		],
		'optionalSets' => [
			['mobile', 'lastname'],
		],
		'mergeKeys' => ['email', 'mobile'],
	],
	'Leads' => [
		'requiredSets' => [
			['email'],
		],
		'optionalSets' => [
			['company', 'lastname'],
			['phone'],
		],
		'mergeKeys' => ['email', 'phone'],
	],
];

