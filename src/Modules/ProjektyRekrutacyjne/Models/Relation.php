<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

namespace App\Modules\ProjektyRekrutacyjne\Models;

use App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers;

/**
 * Relation Model for ProjektyRekrutacyjne module.
 */
class Relation extends \App\Modules\Base\Models\Relation
{
	private const KANDYDACI_MODULE = 'Kandydaci';

	private const RECRUITMENT_STATUS_REL = 'recruitment_status_rel';

	/**
	 * Get related members (Kandydaci) for the recruitment project.
	 * Uses custom relation table with additional fields like recruitment_status_rel.
	 */
	public function getRelatedMembers()
	{
		$relationHandler = new GetRelatedMembers();
		$relationHandler->setRelationModel($this);
		$relationHandler->getQuery();
	}

	/**
	 * @return \App\Modules\Base\Models\Field[]
	 */
	public function getQueryFields()
	{
		$fields = parent::getQueryFields();
		if (0 !== \strcasecmp(self::KANDYDACI_MODULE, $this->getRelationModuleModel()->getName())) {
			return $fields;
		}

		$fields = $this->insertRecruitmentStatusRelField($fields);
		$this->set('QueryFields', $fields);

		return $fields;
	}

	public static function createRecruitmentStatusRelField(\App\Modules\Base\Models\Module $parentModule): RelationField
	{
		$data = GetRelatedMembers::CUSTOM_FIELDS[self::RECRUITMENT_STATUS_REL];
		$field = new RelationField();
		$field->set('name', self::RECRUITMENT_STATUS_REL)
			->set('column', self::RECRUITMENT_STATUS_REL)
			->set('table', GetRelatedMembers::TABLE_NAME)
			->set('tabid', $parentModule->getId())
			->set('label', 'LBL_STATUS_REL')
			->set('displaytype', 1)
			->set('presence', 2)
			->set('fromOutsideList', true)
			->setModule($parentModule);

		foreach ($data as $key => $value) {
			if ('type' === $key) {
				continue;
			}
			$field->set($key, $value);
		}
		$field->set('uitype', 15);
		$field->setFieldInfo(['searchOperator' => 'e']);

		return $field;
	}

	/**
	 * @param \App\Modules\Base\Models\Field[] $fields
	 *
	 * @return \App\Modules\Base\Models\Field[]
	 */
	private function insertRecruitmentStatusRelField(array $fields): array
	{
		if (isset($fields[self::RECRUITMENT_STATUS_REL])) {
			return $fields;
		}

		$statusField = self::createRecruitmentStatusRelField($this->getParentModuleModel());
		$result = [];
		$inserted = false;
		foreach ($fields as $name => $fieldModel) {
			if ('id' === $name) {
				continue;
			}
			$result[$name] = $fieldModel;
			if ('name' === $name) {
				$result[self::RECRUITMENT_STATUS_REL] = $statusField;
				$inserted = true;
			}
		}
		if (!$inserted) {
			$result[self::RECRUITMENT_STATUS_REL] = $statusField;
		}
		if (isset($fields['id'])) {
			$result['id'] = $fields['id'];
		}

		return $result;
	}
}
