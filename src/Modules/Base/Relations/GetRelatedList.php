<?php

namespace App\Modules\Base\Relations;

/* +***********************************************************************************
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 * *********************************************************************************** */

/**
 * Base class for relation types.
 * 
 * This class provides base functionality for custom relation types
 * that need to handle specific M:M relation logic.
 */
class GetRelatedList
{
    /** @var string Table name for the relation */
    public const TABLE_NAME = 'vtiger_crmentityrel';

    /** @var \App\Modules\Base\Models\Relation */
    protected $relationModel;

    /**
     * Set the relation model.
     *
     * @param \App\Modules\Base\Models\Relation $relationModel
     * @return $this
     */
    public function setRelationModel(\App\Modules\Base\Models\Relation $relationModel)
    {
        $this->relationModel = $relationModel;
        return $this;
    }

    /**
     * Get the relation model.
     *
     * @return \App\Modules\Base\Models\Relation
     */
    public function getRelationModel()
    {
        return $this->relationModel;
    }

    /**
     * Get query for the relation.
     * Override in subclasses to add custom fields/joins.
     */
    public function getQuery()
    {
        $queryGenerator = $this->relationModel->getQueryGenerator();
        $record = $this->relationModel->get('parentRecord')->getId();
        $queryGenerator->addJoin(['INNER JOIN', 'vtiger_crmentityrel', '(vtiger_crmentityrel.relcrmid = vtiger_crmentity.crmid OR vtiger_crmentityrel.crmid = vtiger_crmentity.crmid)']);
        $queryGenerator->addNativeCondition(['or', ['vtiger_crmentityrel.crmid' => $record], ['vtiger_crmentityrel.relcrmid' => $record]]);
    }

    /**
     * Create a relation between two records.
     *
     * @param int $sourceRecordId
     * @param int $destinationRecordId
     * @return bool
     */
    public function create(int $sourceRecordId, int $destinationRecordId): bool
    {
        $result = false;
        if (!$this->getRelationData($sourceRecordId, $destinationRecordId)) {
            $result = \App\Db\Db::getInstance()->createCommand()->insert(static::TABLE_NAME, [
                'crmid' => $sourceRecordId,
                'relcrmid' => $destinationRecordId,
            ])->execute();
        }
        return (bool) $result;
    }

    /**
     * Delete a relation between two records.
     *
     * @param int $sourceRecordId
     * @param int $destinationRecordId
     * @return bool
     */
    public function delete(int $sourceRecordId, int $destinationRecordId): bool
    {
        return (bool) \App\Db\Db::getInstance()->createCommand()->delete(static::TABLE_NAME, [
            'or',
            ['crmid' => $sourceRecordId, 'relcrmid' => $destinationRecordId],
            ['crmid' => $destinationRecordId, 'relcrmid' => $sourceRecordId],
        ])->execute();
    }

    /**
     * Get relation data.
     *
     * @param int $sourceRecordId
     * @param int $destinationRecordId
     * @return array|false
     */
    public function getRelationData(int $sourceRecordId, int $destinationRecordId)
    {
        return (new \App\Db\Query())->from(static::TABLE_NAME)->where([
            'or',
            ['crmid' => $sourceRecordId, 'relcrmid' => $destinationRecordId],
            ['crmid' => $destinationRecordId, 'relcrmid' => $sourceRecordId],
        ])->one();
    }

    /**
     * Get custom fields for this relation type.
     *
     * @param bool $editable
     * @return array
     */
    public function getFields(bool $editable = false)
    {
        return [];
    }
}


