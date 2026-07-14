<?php

namespace App\Modules\ProjektyRekrutacyjne\Relations;

/* +***********************************************************************************
 * Includes RelatedMembers relation.
 *
 * @package   Relation
 *
 * @copyright YetiForce S.A.
 * @license   YetiForce Public License 5.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * *********************************************************************************** */

/**
 * Class GetRelatedMembers.
 */
class GetRelatedMembers extends \App\Modules\Base\Relations\GetRelatedList
{
    private const PROJECT_MODULE = 'ProjektyRekrutacyjne';
    private const CANDIDATE_MODULE = 'Candidates';

    public const STATUS_APPLIED = 'PPL_APPLIED';
    public const STATUS_MANUALLY_ADDED = 'PPL_MANUALLY_ADDED';
    public const STATUS_AI_ADDED = 'PPL_AI_ADDED';

    /** {@inheritdoc} */
    public const TABLE_NAME = 'u_yf_projekty_rekrutacyjne_relations_members_entity';

    /**
     * Field custom list.
     *
     * @var array
     */

    public const CUSTOM_FIELDS = [
        'recruitment_status_rel' => [
            'type' => "Multipicklist",
            'label' => 'LBL_STATUS_REL',
//			'uitype' => 16, //picklist
//			'uitype' => 15, //picklist
            'uitype' => 115, //picklist
//			'uitype' => 33, //multipicklist
            'table' => 'u_yf_projekty_rekrutacyjne_relations_members_entity',
            'column' => 'recruitment_status_rel',
            'fieldInfo' => ['searchOperator' => 'c'],
        ],
        'comment_rel' => [
            'type' => "String",
            'label' => 'LBL_COMMENT_REL',
            'uitype' => 21,
            'maximumlength' => 65535,
            'table' => 'u_yf_projekty_rekrutacyjne_relations_members_entity',
            'column' => 'comment_rel',
        ],
        'rel_created_time' => [
            'type' => "Datetime",
            'label' => 'LBL_RELATION_CREATED_TIME',
            'uitype' => 5,
//			'uitype' => 70,
//			'displaytype' => 10,
            'displaytype' => 1,
//			'typeofdata' => 'D~O',
            'typeofdata' => 'DT',
            'info_type' => 'BAS',
            'table' => 'u_yf_projekty_rekrutacyjne_relations_members_entity',
            'column' => 'rel_created_time',
        ],
        'rel_created_user' => [
            'type' => "UserCreator",
            'label' => 'LBL_RELATION_CREATED_USER',
            'uitype' => 52,
            'displaytype' => 10,
            'table' => 'u_yf_projekty_rekrutacyjne_relations_members_entity',
            'column' => 'rel_created_user',
        ],
    ];

    public array $customFields = [
        'recruitment_status_rel' => [
            'type' => "Multipicklist",
            'label' => 'LBL_STATUS_REL',
//			'uitype' => 16, //picklist
//			'uitype' => 15, //picklist
            'uitype' => 115, //picklist
//			'uitype' => 33, //multipicklist
            'table' => 'u_yf_projekty_rekrutacyjne_relations_members_entity',
            'column' => 'recruitment_status_rel',
            'fieldInfo' => ['searchOperator' => 'c'],
        ],
        'comment_rel' => [
            'type' => "String",
            'label' => 'LBL_COMMENT_REL',
            'uitype' => 21,
            'maximumlength' => 65535,
            'table' => 'u_yf_projekty_rekrutacyjne_relations_members_entity',
            'column' => 'comment_rel',
        ],
        'rel_created_time' => [
            'type' => "Datetime",
            'label' => 'LBL_RELATION_CREATED_TIME',
            'uitype' => 5,
//			'uitype' => 70,
//			'displaytype' => 10,
            'displaytype' => 1,
//			'typeofdata' => 'D~O',
            'typeofdata' => 'DT',
            'info_type' => 'BAS',
            'table' => 'u_yf_projekty_rekrutacyjne_relations_members_entity',
            'column' => 'rel_created_time',
        ],
        'rel_created_user' => [
            'type' => "UserCreator",
            'label' => 'LBL_RELATION_CREATED_USER',
            'uitype' => 52,
            'displaytype' => 10,
            'table' => 'u_yf_projekty_rekrutacyjne_relations_members_entity',
            'column' => 'rel_created_user',
        ],
    ];

    /**
     * Field list.
     *
     * @param bool $editable
     *
     * @return array
     */
    public function getFields(bool $editable = false)
    {
        $fields = [];
        $sourceModule = $this->relationModel->getParentModuleModel();
        if ('Occurrences' !== $sourceModule->getName()) {
            $sourceModule = $this->relationModel->getRelationModuleModel();
        }
//		$fieldsIds=[3938,3939,3940,3941];
//		foreach ($fieldsIds as $fieldId) {
//			$field= \Vtiger_Field_Model::getInstanceFromFieldId($fieldId);
//			if($field->getName()==='recruitment_status_rel'){
//				$field->setFieldInfo(['searchOperator' => 'c']);
//			}
//			$a = $field->getLabel();
//			$field->setModule($sourceModule);
//
//			$fields[] = $field;
//		}
        foreach ($this->customFields as $fieldName => $data) {
            $field = new \App\Modules\Base\Models\Field();
            $field->set('name', $fieldName)->set('column', $fieldName)->set('table', static::TABLE_NAME)->set('fromOutsideList', false)->setModule($sourceModule);

            foreach ($data as $key => $value) {
                $field->set($key, $value);
            }
            if (!$editable || !$field->isEditableReadOnly()) {
                $fields[$fieldName] = $field;
            }
        }

        return $fields;
    }

    public static function compareAttributes($obj1, $obj2)
    {
        $differences = [];
        $reflect1 = new \ReflectionClass($obj1);
        $reflect2 = new \ReflectionClass($obj2);

        $props1 = $reflect1->getProperties();
        $props2 = $reflect2->getProperties();

        foreach ($props1 as $prop) {
            $prop->setAccessible(true);  // Make private/protected properties accessible
            $val1 = $prop->getValue($obj1);
            $val2 = $prop->getValue($obj2);

            if ($val1 !== $val2) {
                $item["const"] = $val1;
                $item["db"] = $val2;
                $differences[$prop->getName()] = $item;
            }
        }
        return $differences;
    }

    /** {@inheritdoc} */
    public function getQuery()
    {
        $tableName = static::TABLE_NAME;
        $queryGenerator = $this->relationModel->getQueryGenerator();
        $record = $this->relationModel->get('parentRecord')->getId();
        
        // Add custom columns from the relation table
        foreach (array_keys($this->customFields) as $fieldName) {
            $queryGenerator->setCustomColumn([$fieldName => "{$tableName}.{$fieldName}"]);
        }
        
        // Directional relation for this view:
        // parent project is stored in crmid, related candidate in relcrmid.
        $queryGenerator->addJoin(['INNER JOIN', $tableName, "{$tableName}.relcrmid = vtiger_crmentity.crmid"]);
        $queryGenerator->addNativeCondition(["{$tableName}.crmid" => $record]);
        if (0 === \strcasecmp((string) $this->relationModel->get('label'), 'Screening')) {
            $queryGenerator->addNativeCondition(["{$tableName}.recruitment_status_rel" => 'PPL_APPLIED']);
        }
    }

    public function create(int $sourceRecordId, int $destinationRecordId): bool
    {
        throw new \App\Exceptions\AppException(
            'Recruitment relation requires explicit status; use createMembership() instead of create().'
        );
    }

    public function createMembership(int $sourceRecordId, int $destinationRecordId, string $initialStatus): bool
    {
        [$sourceRecordId, $destinationRecordId] = $this->normalizeRelationDirection($sourceRecordId, $destinationRecordId);
        if ($this->getRelationData($sourceRecordId, $destinationRecordId)) {
            return false;
        }

        return (bool) \App\Db\Db::getInstance()->createCommand()->insert(static::TABLE_NAME, [
            'crmid' => $sourceRecordId,
            'relcrmid' => $destinationRecordId,
            'recruitment_status_rel' => $initialStatus,
            'rel_created_user' => (int) (\App\User\CurrentUser::getId() ?? 0),
            'rel_created_time' => date('Y-m-d H:i:s'),
        ])->execute();
    }

    public function createLink(int $projectId, int $candidateId, string $initialStatus): bool
    {
        if (!$this->createMembership($projectId, $candidateId, $initialStatus)) {
            return false;
        }

        $handler = new \App\Modules\Candidates\Handlers\NewCandidateInProject();
        $handler->onCandidateLinkedToProject($candidateId, $projectId);

        return true;
    }

    public function delete(int $sourceRecordId, int $destinationRecordId): bool
    {
        [$projectId, $candidateId] = $this->normalizeRelationDirection($sourceRecordId, $destinationRecordId);

        return (bool) \App\Db\Db::getInstance()->createCommand()->delete(static::TABLE_NAME, [
            'crmid' => $projectId,
            'relcrmid' => $candidateId,
        ])->execute();
    }

    /**
     * updateRelationData function.
     *
     * @param int $sourceRecordId
     * @param int $destinationRecordId
     * @param array $updateData
     *
     * @return bool
     */
    public function updateRelationData(int $sourceRecordId, int $destinationRecordId, array $updateData, bool $saveProject = true): bool
    {
        [$sourceRecordId, $destinationRecordId] = $this->normalizeRelationDirection($sourceRecordId, $destinationRecordId);
        $conditions = ['crmid' => $sourceRecordId, 'relcrmid' => $destinationRecordId];
        $result = (bool)$this->getRelationData($sourceRecordId, $destinationRecordId);
        if ($result) {
            $result = (bool)\App\Db\Db::getInstance()->createCommand()->update(static::TABLE_NAME, $updateData, $conditions)->execute();
        }

        if ($saveProject) {
            $this->saveProjectCountersWithWorkflowDisabled($sourceRecordId);
        }
        return $result;
    }

    protected function saveProjectCountersWithWorkflowDisabled(int $projectId): void
    {
        try {
            $project = \App\Modules\Base\Models\Record::getInstanceById($projectId, 'ProjektyRekrutacyjne');
            $project->calculateNumberOfCandidatesInProject();
            $project->setHandlerExceptions(['disableWorkflow' => true]);
            $project->save();
        } catch (\Exception $e) {
        }
    }

    public function changeStatus(int $projectId, int $candidateId, string $sourceStatus, string $destinationStatus): bool
    {
        if ($sourceStatus === $destinationStatus) {
            return true;
        }

        if (!\App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::isAllowed($sourceStatus, $destinationStatus)) {
            return false;
        }

        try {
            $relationBefore = $this->getRelationData($projectId, $candidateId) ?: [];
            $sourceStatusTranslated = \App\Language::translate($sourceStatus, 'ProjektyRekrutacyjne');
            $destinationStatusTranslated = \App\Language::translate($destinationStatus, 'ProjektyRekrutacyjne');
            $updateData = [
                'recruitment_status_rel' => $destinationStatus,
            ];
            $status = $this->updateRelationData($projectId, $candidateId, $updateData, false);
            if ($status) {
                $relationAfter = $this->getRelationData($projectId, $candidateId) ?: [];
                $context = new \App\Modules\Workflow\RelationWorkflowContext(
                    'ProjektyRekrutacyjne',
                    $projectId,
                    'Candidates',
                    $candidateId,
                    static::TABLE_NAME,
                    'recruitment_status_rel',
                    $relationBefore,
                    $relationAfter,
                    $sourceStatus,
                    $destinationStatus,
                    (int) (\App\User\CurrentUser::getId() ?? 0)
                );
                \App\Modules\Workflow\RelationWorkflowRunner::run($context);
            }
            if ($status) {
                $candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId, "Candidates");
                $commentContentForProject = "Status kandydata " . $candidate->getName() . " w projekcie zmieniony z '" . $sourceStatusTranslated . "' na '" . $destinationStatusTranslated . "'";
                $commentForProject = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
                $commentForProject->set('assigned_user_id', \App\Modules\Users\Models\Record::getCurrentUserRealId());
                $commentForProject->set('related_to', $projectId);
                $commentForProject->set('commentcontent', $commentContentForProject);
                $commentForProject->save();

                $project = \App\Modules\Base\Models\Record::getInstanceById($projectId, "ProjektyRekrutacyjne");
                $commentContentForCandidate = "Status w projekcie " . $project->getName() . " zmieniony z '" . $sourceStatusTranslated . "' na '" . $destinationStatusTranslated . "'";
                $commentForCandidate = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
                $commentForCandidate->set('assigned_user_id', \App\Modules\Users\Models\Record::getCurrentUserRealId());
                $commentForCandidate->set('related_to', $candidateId);
                $commentForCandidate->set('commentcontent', $commentContentForCandidate);
                $commentForCandidate->save();

                $this->saveProjectCountersWithWorkflowDisabled($projectId);
            }

        } catch (\Exception $e) {
            \App\Log\Log::error("Error " . $e->getMessage());
            return false;
        }
        return $status;
    }

    /**
     * Get relation data.
     *
     * @param int $sourceRecordId
     * @param int $destinationRecordId
     *
     * @return array
     */
    public function getRelationData(int $sourceRecordId, int $destinationRecordId)
    {
        [$sourceRecordId, $destinationRecordId] = $this->normalizeRelationDirection($sourceRecordId, $destinationRecordId);
        return (new \App\Db\Query())->from(static::TABLE_NAME)->where([
            'crmid' => $sourceRecordId,
            'relcrmid' => $destinationRecordId,
        ])->one();
    }

    /**
     * Force canonical direction in relation table:
     * crmid = project, relcrmid = candidate.
     */
    private function normalizeRelationDirection(int $sourceRecordId, int $destinationRecordId): array
    {
        $sourceModule = (string) \App\Utils\ModuleUtils::getModuleName($sourceRecordId);
        $destinationModule = (string) \App\Utils\ModuleUtils::getModuleName($destinationRecordId);
        if (self::PROJECT_MODULE === $sourceModule && self::CANDIDATE_MODULE === $destinationModule) {
            return [$sourceRecordId, $destinationRecordId];
        }
        if (self::CANDIDATE_MODULE === $sourceModule && self::PROJECT_MODULE === $destinationModule) {
            return [$destinationRecordId, $sourceRecordId];
        }
        return [$sourceRecordId, $destinationRecordId];
    }
}
