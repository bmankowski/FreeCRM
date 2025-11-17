<?php

/* +***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce S.A.
* *********************************************************************************** */

class ProjektyRekrutacyjne_RelationListView_Model extends Vtiger_RelationListView_Model
{



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
		$queryGenerator = $this->relationModel->getQueryGenerator();
		//BMN
		//@author Bartłomiej Mańkowski
		//Add custom fields to query
		$queryFields = [];
		foreach (ProjektyRekrutacyjne_GetRelatedMembers_Relation::CUSTOM_FIELDS as $fieldName => $data) {
			$field = new \Vtiger_Field_Model();
			$sourceModule = $this->relationModel->getParentModuleModel();
			$field->set('name', $fieldName)->set('column', $fieldName)->set('table', ProjektyRekrutacyjne_GetRelatedMembers_Relation::TABLE_NAME)->set('fromOutsideList', false)->setModule($sourceModule);

			foreach ($data as $key => $value) {
				$field->set($key, $value);
			}

			$className = '\App\Conditions\QueryFields\\' . ucfirst($data['type']) . 'Field';
			if (!class_exists($className)) {
				\App\Log::error('Not found query relation field condition: class $className not found');
				throw new \App\Exceptions\AppException('ERR_NOT_FOUND_QUERY_FIELD_CONDITION|' . $fieldName);
			}
			$queryField = new $className($queryGenerator, $field);

			$queryFields[$fieldName] = $queryField;
		}
		$queryGenerator->setQueryFields($queryFields);

		$this->loadCustomView();
		$this->loadCondition();
		$this->loadOrderBy();
		$relationModelInstance = $this->getRelationModel();
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
