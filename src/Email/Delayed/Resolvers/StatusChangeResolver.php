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

namespace App\Email\Delayed\Resolvers;

use App\Email\Delayed\RelevanceResolver;
use App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers;

final class StatusChangeResolver implements RelevanceResolver
{
	public function hash(int $sourceId, int $destId): string
	{
		$row = (new \App\Db\Query())
			->select(['recruitment_status_rel'])
			->from(GetRelatedMembers::TABLE_NAME)
			->where([
				'or',
				['crmid' => $sourceId, 'relcrmid' => $destId],
				['crmid' => $destId, 'relcrmid' => $sourceId],
			])
			->one();

		$state = $row['recruitment_status_rel'] ?? '';
		return hash('sha256', (string) $state);
	}
}
