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
	/**
	 * Function to get related list links.
	 *
	 * @return array
	 */
	public function getLinks()
	{
		$relatedLinks = parent::getLinks();
		$relationModel = $this->getRelationModel();
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$relatedModuleName = $relatedModuleModel->getName();
		if ('Kandydaci' === $relatedModuleName
			&& $relatedModuleModel->isPermitted('MassComposeEmail')
			&& \App\Core\AppConfig::main('isActiveSendingMails')
			&& \App\Email\Mail::getDefaultSmtp()
		) {
			$emailLink = \App\Modules\Base\Models\Link::getInstanceFromValues([
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SEND_EMAIL', $relatedModuleName),
				'linkurl' => 'javascript:ProjektyRekrutacyjne_RelatedList_Js.triggerSendEmail();',
				'linkicon' => '',
			]);
			$emailLink->set('_sendEmail', true);
			$relatedLinks['LISTVIEWBASIC'][] = $emailLink;
		}
		return $relatedLinks;
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
        $queryGenerator = $relationModelInstance->getQueryGenerator();
        //BMN
        //@author Bartłomiej Mańkowski
        //Add custom fields to query
        $queryFields = [];
        foreach (GetRelatedMembers::CUSTOM_FIELDS as $fieldName => $data) {
            $field = new \App\Modules\Base\Models\Field();
            $sourceModule = $relationModelInstance->getParentModuleModel();
            $field->set('name', $fieldName)->set('column', $fieldName)->set('table', GetRelatedMembers::TABLE_NAME)->set('fromOutsideList', false)->setModule($sourceModule);

            foreach ($data as $key => $value) {
                $field->set($key, $value);
            }

            $className = '\App\QueryField\\' . ucfirst($data['type']) . 'Field';
            if (!class_exists($className)) {
                \App\Log\Log::error("Not found query relation field condition: class {$className} not found");
                throw new \App\Exceptions\AppException('ERR_NOT_FOUND_QUERY_FIELD_CONDITION|' . $fieldName);
            }
            $queryField = new $className($queryGenerator, $field);

            $queryFields[$fieldName] = $queryField;
        }
        $queryGenerator->setQueryFields($queryFields);

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
