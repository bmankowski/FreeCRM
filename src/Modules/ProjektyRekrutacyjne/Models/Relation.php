<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\ProjektyRekrutacyjne\Models;

use App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers;

/**
 * Relation Model for ProjektyRekrutacyjne module.
 */
class Relation extends \App\Modules\Base\Models\Relation
{
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
}

