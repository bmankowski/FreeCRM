<?php

namespace App\Modules\Contacts\Models;

use App\Modules\Base\Models\Link;

/**
 * FreeCRM — OSSMail {@see Link}s for RelatedList when the related record is a Contact.
 *
 * @copyright FreeCRM
 * @license FreeCRM Public License 1.1
 */
class RelatedListLeftSideMail
{
	/**
	 * @param array<string, mixed> $context must include ossMailUrls, canSendMails
	 * @return Link[]
	 */
	public static function asLinks(int $recordId, array $context): array
	{
		$canSendMails = !empty($context['canSendMails']);
		$ossMailUrls = $context['ossMailUrls'] ?? [];
		if (!$canSendMails || empty($ossMailUrls[$recordId])) {
			return [];
		}

		return self::linksFromOssMailEntry($ossMailUrls[$recordId]);
	}

	/**
	 * @param array{type:string,url:string} $mail
	 * @return Link[]
	 */
	protected static function linksFromOssMailEntry(array $mail): array
	{
		if ($mail['type'] === 'compose') {
			return [Link::getInstanceFromValues([
				'linktype' => \App\Modules\Base\Models\RelatedListLeftSideLinks::LINK_TYPE,
				'linklabel' => 'LBL_SEND_EMAIL',
				'linkurl' => $mail['url'],
				'linkicon' => 'glyphicon glyphicon-envelope',
				'linkclass' => '',
				'linkhref' => true,
				'linktarget' => '_blank',
				'relatedModuleName' => 'Vtiger',
			])];
		}

		return [Link::getInstanceFromValues([
			'linktype' => \App\Modules\Base\Models\RelatedListLeftSideLinks::LINK_TYPE,
			'linklabel' => 'LBL_CREATEMAIL',
			'linkurl' => $mail['url'],
			'linkicon' => 'glyphicon glyphicon-envelope',
			'linkclass' => '',
			'linkhref' => true,
			'relatedModuleName' => 'OSSMailView',
		])];
	}
}
