<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

namespace App\Security;

/**
 * data: URI scheme for HTMLPurifier — extends defaults with WebP (company mail logos).
 */
class HtmlPurifierDataUriScheme extends \HTMLPurifier_URIScheme_data
{
	/** @var array<string, bool> */
	public $allowed_types = [
		'image/jpeg' => true,
		'image/gif' => true,
		'image/png' => true,
		'image/webp' => true,
	];
}
