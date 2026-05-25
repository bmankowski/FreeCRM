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

namespace App\Email\Delayed;

interface RelevanceResolver
{
	/**
	 * SHA-256 hex digest of domain state for (source, dest). Must change iff the buffered email should no longer go out.
	 */
	public function hash(int $sourceId, int $destId): string;
}
