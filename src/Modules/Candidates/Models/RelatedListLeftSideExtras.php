<?php

namespace App\Modules\Candidates\Models;

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
		if ($parentRecord->getModuleName() !== 'Candidates' || $relatedModule->getName() !== 'Documents') {
			return $links;
		}
		// Do not require relationIsEditable (that follows Related module EditView — too strict for “set as CV”,
		// which updates the candidate, not the document). Also, Candidate can be non-editable due to record state
		// (locks/workflows) even though “set as CV” is still allowed by business logic — the Action will enforce perms.
		if (!$parentRecord->isViewable() || !$relatedRecord->isViewable()) {
			return $links;
		}

		$modalUrl = 'index.php?module=Candidates&view=TransformDocumentToCVModal&candidateId=' . $parentRecord->getId()
			. '&documentId=' . $relatedRecord->getId();

		$cvLink = Link::getInstanceFromValues([
			'linktype' => \App\Modules\Base\Models\RelatedListLeftSideLinks::LINK_TYPE,
			'linklabel' => 'CV',
			'linkurl' => $modalUrl,
			'linkicon' => '',
			'showLabel' => true,
			'linkclass' => 'btn btn-sm btn-info js-show-modal  ',
			'modalView' => true,
			'active' => true,
			'relatedModuleName' => 'Candidates',
		]);

		// Avoid duplicates (e.g. if some other layer already injected this action).
		foreach ($links as $link) {
			if ($link->get('linklabel') === 'LBL_RELATED_ACTION_SET_AS_CV' || strpos((string) $link->get('linkurl'), 'view=TransformDocumentToCVModal') !== false) {
				return $links;
			}
		}
		$mergedLinks = array_merge([$cvLink], $links);
		// Make “Set as CV” the first (most visible) action.
		return $mergedLinks;
	}

	protected static function isRelationDeleteLink(Link $link): bool
	{
		$class = (string) $link->getClassName();
		return $link->getLabel() === 'LBL_DELETE' || strpos($class, 'relationDelete') !== false;
	}
}
