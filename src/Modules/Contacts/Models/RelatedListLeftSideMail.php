<?php

namespace App\Modules\Contacts\Models;

use App\Modules\Base\Models\Link;

/**
 * FreeCRM — Mail compose link for RelatedList when the related record is a Contact.
 */
class RelatedListLeftSideMail
{
	/**
	 * @param array<string, mixed> $context must include mailComposeUrls, canSendMails
	 * @return Link[]
	 */
	public static function asLinks(int $recordId, array $context): array
	{
		$canSendMails = !empty($context['canSendMails']);
		$mailUrls = $context['mailComposeUrls'] ?? [];
		if (!$canSendMails || empty($mailUrls[$recordId])) {
			return [];
		}

		return [Link::getInstanceFromValues([
			'linktype' => \App\Modules\Base\Models\RelatedListLeftSideLinks::LINK_TYPE,
			'linklabel' => 'LBL_SEND_EMAIL',
			'linkurl' => $mailUrls[$recordId],
			'linkicon' => 'glyphicon glyphicon-envelope',
			'linkclass' => '',
			'linkhref' => true,
			'relatedModuleName' => 'Contacts',
		])];
	}
}
