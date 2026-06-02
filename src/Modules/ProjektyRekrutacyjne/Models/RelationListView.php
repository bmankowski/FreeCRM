<?php

namespace App\Modules\ProjektyRekrutacyjne\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

use App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers;

class RelationListView extends \App\Modules\Base\Models\RelationListView
{
	private bool $relationQueryFieldsRegistered = false;

	private const KANDYDACI_RELATED_ACTION_LABELS = [
		'LBL_MASS_SEND_EMAIL',
		'LBL_QUICK_EXPORT_TO_EXCEL',
		'LBL_EXPORT',
		'PPL_EXPORT_CV',
	];

	/**
	 * Function to get related list links.
	 *
	 * @return array
	 */
	public function getLinks()
	{
		$relatedLinks = parent::getLinks();
		$relatedModuleName = $this->getRelationModel()->getRelationModuleModel()->getName();
		if ('Kandydaci' !== $relatedModuleName) {
			return $relatedLinks;
		}

		$massActions = $this->getKandydaciRelatedMassActions();
		if ([] !== $massActions) {
			$relatedLinks['RELATEDLIST_MASSACTIONS'] = $massActions;
		}

		if (isset($relatedLinks['LISTVIEWBASIC'])) {
			$relatedLinks['LISTVIEWBASIC'] = array_values(array_filter(
				$relatedLinks['LISTVIEWBASIC'],
				static fn($link): bool => !(bool) $link->get('_sendEmail')
					&& !str_contains((string) $link->getUrl(), 'triggerSendEmail')
			));
		}

		return $relatedLinks;
	}

	/**
	 * @return \App\Modules\Base\Models\Link[]
	 */
	private function getKandydaciRelatedMassActions(): array
	{
		$relatedModuleName = 'Kandydaci';
		$relatedModuleModel = $this->getRelationModel()->getRelationModuleModel();
		$listViewModel = \App\Modules\Base\Models\ListView::getInstance($relatedModuleName);
		$linkParams = ['MODULE' => $relatedModuleName, 'ACTION' => 'Detail'];

		$links = [];
		$massActionLinks = $listViewModel->getListViewMassActions($linkParams)['LISTVIEWMASSACTION'] ?? [];
		foreach ($massActionLinks as $link) {
			if (!$this->isKandydaciRelatedActionLabel((string) $link->get('linklabel'))) {
				continue;
			}
			$links[] = $this->mapKandydaciLinkForRelatedList($link, $relatedModuleModel);
		}

		foreach ($listViewModel->getAdvancedLinks() as $advancedLink) {
			if (!\in_array($advancedLink['linktype'], ['LISTVIEW', 'LISTVIEWMASSACTION'], true)) {
				continue;
			}
			if (!$this->isKandydaciRelatedActionLabel((string) $advancedLink['linklabel'])) {
				continue;
			}
			$links[] = $this->mapKandydaciLinkForRelatedList(
				\App\Modules\Base\Models\Link::getInstanceFromValues($advancedLink),
				$relatedModuleModel
			);
		}

		return $links;
	}

	private function isKandydaciRelatedActionLabel(string $label): bool
	{
		return \in_array($label, self::KANDYDACI_RELATED_ACTION_LABELS, true);
	}

	private function mapKandydaciLinkForRelatedList(
		\App\Modules\Base\Models\Link $link,
		\App\Modules\Base\Models\Module $relatedModuleModel
	): \App\Modules\Base\Models\Link {
		$url = (string) $link->getUrl();
		if (str_contains($url, 'triggerSendEmail')) {
			$link->set('linkurl', 'javascript:ProjektyRekrutacyjne_RelatedList_Js.triggerSendEmail();');
		} elseif (str_contains($url, 'triggerQuickExportToExcel')) {
			$link->set('linkurl', 'javascript:ProjektyRekrutacyjne_RelatedList_Js.triggerQuickExportToExcel();');
		} elseif (str_contains($url, 'triggerExportAction')) {
			$exportUrl = $relatedModuleModel->getExportUrl();
			$link->set('linkurl', 'javascript:ProjektyRekrutacyjne_RelatedList_Js.triggerExportAction("' . $exportUrl . '");');
		} elseif (str_contains($url, 'triggerExportCvZip')) {
			$link->set('linkurl', 'javascript:ProjektyRekrutacyjne_RelatedList_Js.triggerExportCvZip();');
		}

		return $link;
	}

	/**
	 * @return \App\Modules\Base\Models\Field[]
	 */
	public function getHeaders()
	{
		$headers = parent::getHeaders();
		if ('Kandydaci' !== $this->getRelatedModuleModel()->getName()) {
			return $headers;
		}
		if (isset($headers['recruitment_status_rel'])) {
			return $headers;
		}

		$statusField = Relation::createRecruitmentStatusRelField(
			$this->getRelationModel()->getParentModuleModel()
		);
		$result = [];
		$inserted = false;
		foreach ($headers as $name => $fieldModel) {
			$result[$name] = $fieldModel;
			if ('name' === $name) {
				$result['recruitment_status_rel'] = $statusField;
				$inserted = true;
			}
		}
		if (!$inserted) {
			$result['recruitment_status_rel'] = $statusField;
		}

		return $result;
	}

	/**
	 * Register relation-table query fields on the query generator (must run before list search parsing).
	 */
	public function registerRelationQueryFields(): void
	{
		if ($this->relationQueryFieldsRegistered || 'Kandydaci' !== $this->getRelatedModuleModel()->getName()) {
			return;
		}

		$relationModelInstance = $this->getRelationModel();
		$queryGenerator = $relationModelInstance->getQueryGenerator();
		$sourceModule = $relationModelInstance->getParentModuleModel();
		$queryFields = [];

		foreach (GetRelatedMembers::CUSTOM_FIELDS as $fieldName => $data) {
			if ('recruitment_status_rel' === $fieldName) {
				$field = Relation::createRecruitmentStatusRelField($sourceModule);
				$queryType = 'Picklist';
			} else {
				$field = new \App\Modules\Base\Models\Field();
				$field->set('name', $fieldName)
					->set('column', $fieldName)
					->set('table', GetRelatedMembers::TABLE_NAME)
					->set('tabid', $sourceModule->getId())
					->set('fromOutsideList', false)
					->setModule($sourceModule);

				foreach ($data as $key => $value) {
					if ('type' === $key) {
						continue;
					}
					$field->set($key, $value);
				}
				$queryType = $data['type'];
			}

			$className = '\App\QueryField\\' . ucfirst($queryType) . 'Field';
			if (!class_exists($className)) {
				\App\Log\Log::error("Not found query relation field condition: class {$className} not found");
				throw new \App\Exceptions\AppException('ERR_NOT_FOUND_QUERY_FIELD_CONDITION|' . $fieldName);
			}
			$queryFields[$fieldName] = new $className($queryGenerator, $field);
		}

		$queryGenerator->setQueryFields($queryFields);
		$this->relationQueryFieldsRegistered = true;
	}

    /**
     * Function to get Relation query.
     *
     * @param mixed $returnQueryGenerator
     *
     * @return \App\Db\Query|\App\QueryGenerator
     */
    public function getRelationQuery($returnQueryGenerator = false)
    {
        if ($this->has('Query')) {
            return $this->get('Query');
        }
        $relationModelInstance = $this->getRelationModel();
        if (empty($relationModelInstance)) {
            throw new \App\Exceptions\AppException('>>> No relationModel instance, requires verification 2 <<<');
        }
        $this->registerRelationQueryFields();

        $this->loadCondition();
        $this->loadOrderBy();
        if (!empty($relationModelInstance) && $relationModelInstance->get('name')) {
            $queryGenerator = $relationModelInstance->getQuery();
            $relationModuleName = $queryGenerator->getModule();
            if (isset($this->mandatoryColumns[$relationModuleName])) {
                foreach ($this->mandatoryColumns[$relationModuleName] as &$columnName) {
                    $queryGenerator->setCustomColumn($columnName);
                }
            }
            if ($returnQueryGenerator) {
                return $queryGenerator;
            }
            $query = $queryGenerator->createQuery();
            $this->set('Query', $query);
            $sql = $query->createCommand()->getRawSql();
            return $query;
        }
        throw new \App\Exceptions\AppException('>>> No relationModel instance, requires verification 2 <<<');
    }
}
