<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Canonical configuration describing duplicate-detection sets for ImportManager.
 * Each module entry may declare:
 *  - optionalSets: extra sets that can be toggled per batch
 *  - mergeKeys: list of fields used when merging multi-value data
 * Mandatory field requirements are derived automatically from vtiger_field definitions.
 */

declare(strict_types=1);

return [
	'Contacts' => [
		'optionalSets' => [
			['mobile', 'lastname'],
		],
		'mergeKeys' => ['email', 'mobile'],
	],
	'Leads' => [
		'optionalSets' => [
			['company', 'lastname'],
			['phone'],
		],
		'mergeKeys' => ['email', 'phone'],
	],
];

