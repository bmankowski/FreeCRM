<?php

namespace App\Modules\Kandydaci\Models;

use App\Modules\Base\Models\Link;
use App\Modules\Base\Models\Module;
use App\Modules\Base\Models\Record;

/**
 * FreeCRM — extra RelatedList left-menu {@see Link}s for Candidates + Documents.
 *
 * @copyright FreeCRM
 * @license FreeCRM Public License 1.1
 */
class RelatedListLeftSideExtras
{
	/**
	 * @param Link[] $links
	 * @param array<string, mixed> $context
	 * @return Link[]
	 */
	public static function mergeLinks(array $links, Record $parentRecord, Record $relatedRecord, Module $relatedModule, array $context): array
	{
		$relationIsEditable = $context['relationIsEditable'] ?? false;
		if ($parentRecord->getModuleName() !== 'Kandydaci' || $relatedModule->getName() !== 'Documents') {
			return $links;
		}
		if (!$relationIsEditable || !$parentRecord->isEditable() || !$relatedRecord->isViewable()) {
			return $links;
		}

		$modalUrl = 'index.php?module=Kandydaci&view=TransformDocumentToCVModal&candidateId=' . $parentRecord->getId()
			. '&documentId=' . $relatedRecord->getId();

		$cvLink = Link::getInstanceFromValues([
			'linktype' => \App\Modules\Base\Models\RelatedListLeftSideLinks::LINK_TYPE,
			'linklabel' => 'LBL_RELATED_ACTION_SET_AS_CV',
			'linkurl' => $modalUrl,
			'linkicon' => 'glyphicon glyphicon-file',
			'linkclass' => '',
			'modalView' => true,
			'relatedModuleName' => 'Kandydaci',
		]);

		$inserted = false;
		$out = [];
		foreach ($links as $link) {
			if (!$inserted && self::isRelationDeleteLink($link)) {
				$out[] = $cvLink;
				$inserted = true;
			}
			$out[] = $link;
		}
		if (!$inserted) {
			$out[] = $cvLink;
		}

		return $out;
	}

	protected static function isRelationDeleteLink(Link $link): bool
	{
		$class = (string) $link->getClassName();
		return $link->getLabel() === 'LBL_DELETE' || strpos($class, 'relationDelete') !== false;
	}
}
