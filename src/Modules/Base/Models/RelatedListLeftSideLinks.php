<?php

namespace App\Modules\Base\Models;

/**
 * FreeCRM — builds {@see Link} models for RelatedList left column (same pattern as list view links).
 *
 * @copyright FreeCRM
 * @license FreeCRM Public License 1.1
 */
class RelatedListLeftSideLinks
{
	public const LINK_TYPE = 'RELATED_LIST_LEFT_SIDE';

	/**
	 * Context keys: parentModuleName, relationIsEditable, relationIsDeletable, currentActivityLabels (?).
	 *
	 * @param array<string, mixed> $context
	 * @return Link[]
	 */
	public static function create(Record $related, Record $parent, array $context): array
	{
		$relatedModule = $related->getModule();
		$relatedModuleName = $related->getModuleName();
		$parentModuleName = $context['parentModuleName'];
		$relationIsEditable = $context['relationIsEditable'];
		$relationIsDeletable = $context['relationIsDeletable'];
		$currentActivityLabels = $context['currentActivityLabels'] ?? null;

		$links = [];

		if ($relatedModule->isPermitted('WatchingRecords') && $related->isViewable()) {
			$watchingState = (int) !$related->isWatchingRecord();
			$links[] = Link::getInstanceFromValues([
				'linktype' => self::LINK_TYPE,
				'linklabel' => 'BTN_WATCHING_RECORD',
				'linkurl' => 'javascript:Vtiger_Index_Js.changeWatching(this)',
				'linkicon' => 'glyphicon ' . ($watchingState ? 'glyphicon-eye-close' : 'glyphicon-eye-open'),
				'linkclass' => 'noLinkBtn' . ($watchingState ? '' : ' info-color'),
				'linkdata' => [
					'record' => (string) $related->getId(),
					'value' => (string) $watchingState,
					'on' => 'info-color',
					'off' => '',
					'icon-on' => 'glyphicon-eye-open',
					'icon-off' => 'glyphicon-eye-close',
					'module' => $relatedModuleName,
				],
				'relatedModuleName' => $parentModuleName,
			]);
		}

		if ($relatedModuleName === 'Calendar' && $currentActivityLabels !== null) {
			$status = $related->getValueByField('activitystatus');
			if ($relationIsEditable && \in_array($status, $currentActivityLabels)) {
				$links[] = Link::getInstanceFromValues([
					'linktype' => self::LINK_TYPE,
					'linklabel' => 'LBL_SET_RECORD_STATUS',
					'linkurl' => $related->getActivityStateModalUrl(),
					'linkicon' => 'glyphicon glyphicon-ok',
					'linkclass' => 'showModal',
					'modalView' => true,
					'relatedModuleName' => $parentModuleName,
				]);
			}
		}

		if ($relatedModuleName === 'Calendar') {
			if ($related->isViewable()) {
				$links[] = Link::getInstanceFromValues([
					'linktype' => self::LINK_TYPE,
					'linklabel' => 'LBL_SHOW_COMPLETE_DETAILS',
					'linkurl' => $related->getFullDetailViewUrl(),
					'linkicon' => 'glyphicon glyphicon-th-list',
					'linkclass' => '',
					'linkhref' => true,
					'relatedModuleName' => $parentModuleName,
				]);
			}
		} else {
			$links[] = Link::getInstanceFromValues([
				'linktype' => self::LINK_TYPE,
				'linklabel' => 'LBL_SHOW_COMPLETE_DETAILS',
				'linkurl' => $related->getFullDetailViewUrl(),
				'linkicon' => 'glyphicon glyphicon-th-list',
				'linkclass' => '',
				'linkhref' => true,
				'relatedModuleName' => $parentModuleName,
			]);
		}

		if ($relationIsEditable && $related->isEditable()) {
			if ($parent->getModuleName() === 'PriceBooks') {
				$listPrice = $related->get('listprice');
				$dataUrl = 'index.php?module=PriceBooks&view=ListViewPriceUpdate&record=' . $parent->getId()
					. '&relid=' . $related->getId() . '&currentPrice=' . $listPrice;
				$links[] = Link::getInstanceFromValues([
					'linktype' => self::LINK_TYPE,
					'linklabel' => 'LBL_EDIT',
					'linkurl' => '',
					'linkicon' => 'glyphicon glyphicon-pencil',
					'linkclass' => 'editListPrice cursorPointer',
					'linkdata' => [
						'url' => $dataUrl,
						'related-recordid' => (string) $related->getId(),
						'list-price' => $listPrice,
					],
					'relatedModuleName' => $parentModuleName,
				]);
			} elseif ($relatedModuleName === 'Calendar') {
				if ($related->isEditable()) {
					$links[] = Link::getInstanceFromValues([
						'linktype' => self::LINK_TYPE,
						'linklabel' => 'LBL_EDIT',
						'linkurl' => $related->getEditViewUrl(),
						'linkicon' => 'glyphicon glyphicon-pencil',
						'linkclass' => '',
						'linkhref' => true,
						'relatedModuleName' => $parentModuleName,
					]);
				}
			} else {
				$links[] = Link::getInstanceFromValues([
					'linktype' => self::LINK_TYPE,
					'linklabel' => 'LBL_EDIT',
					'linkurl' => $related->getEditViewUrl(),
					'linkicon' => 'glyphicon glyphicon-pencil',
					'linkclass' => '',
					'linkhref' => true,
					'relatedModuleName' => $parentModuleName,
				]);
			}
		}

		if (($relationIsEditable && $related->isEditable() && $related->editFieldByModalPermission()) || $related->editFieldByModalPermission(true)) {
			$fieldByEdit = $related->getFieldToEditByModal();
			$links[] = Link::getInstanceFromValues([
				'linktype' => self::LINK_TYPE,
				'linklabel' => $fieldByEdit['titleTag'],
				'linkurl' => $related->getEditFieldByModalUrl(),
				'linkicon' => 'glyphicon ' . ($fieldByEdit['iconClass'] ?? ''),
				'linkclass' => $fieldByEdit['listViewClass'] ?? '',
				'modalView' => true,
				'relatedModuleName' => $parentModuleName,
			]);
		}

		if ($relationIsDeletable && $related->isDeletable()) {
			$links[] = Link::getInstanceFromValues([
				'linktype' => self::LINK_TYPE,
				'linklabel' => 'LBL_DELETE',
				'linkurl' => '#',
				'linkicon' => 'glyphicon glyphicon-trash',
				'linkclass' => 'relationDelete',
				'linkhref' => true,
				'relatedModuleName' => $parentModuleName,
			]);
		}

		return $links;
	}
}
