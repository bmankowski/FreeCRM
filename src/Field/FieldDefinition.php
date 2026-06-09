<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Field;

/**
 * Canonical read shape for a vtiger_field row.
 *
 * Property names mirror the DB column names exactly. This eliminates the
 * $field->quicksequence vs vtiger_field.quickcreatesequence class of translation
 * bug. fromRow() tolerates any superset of keys; only the 7 core keys
 * (fieldid, tabid, fieldname, fieldlabel, tablename, columnname, uitype) are
 * required — accessing them on an incomplete row triggers a PHP "undefined
 * array key" error, which is intentional.
 *
 * readonly/mandatory are typed as bool here; toRow() casts them back to int(1)
 * at the persistence boundary so DB code never sees PHP true/false.
 */
final class FieldDefinition
{
    /** FieldDefinition property names that differ from vtiger_field column names. */
    private const PROPERTY_TO_ROW_KEY = [
        'id'     => 'fieldid',
        'name'   => 'fieldname',
        'label'  => 'fieldlabel',
        'table'  => 'tablename',
        'column' => 'columnname',
    ];

    public function __construct(
        public readonly int     $id,                  // vtiger_field.fieldid
        public readonly int     $tabid,               // vtiger_field.tabid
        public readonly string  $name,                // vtiger_field.fieldname
        public readonly string  $label,               // vtiger_field.fieldlabel
        public readonly string  $table,               // vtiger_field.tablename
        public readonly string  $column,              // vtiger_field.columnname
        public readonly ?string $columntype,          // not stored in vtiger_field; derived
        public readonly int     $uitype,              // vtiger_field.uitype
        public readonly string  $typeofdata,          // single token (V, D, N, …)
        public readonly int     $displaytype,         // vtiger_field.displaytype
        public readonly int     $generatedtype,       // vtiger_field.generatedtype (DB DEFAULT 0)
        public readonly bool    $readonly,            // vtiger_field.readonly (tinyint(1))
        public readonly bool    $mandatory,           // vtiger_field.mandatory (tinyint(1))
        public readonly int     $presence,            // vtiger_field.presence (DB DEFAULT 1)
        public readonly string  $defaultvalue,        // vtiger_field.defaultvalue
        public readonly int     $maximumlength,       // vtiger_field.maximumlength
        public readonly int     $sequence,            // vtiger_field.sequence
        public readonly ?int    $block,               // vtiger_field.block (FK, nullable)
        public readonly int     $masseditable,        // vtiger_field.masseditable
        public readonly int     $quickcreate,         // vtiger_field.quickcreate
        public readonly ?int    $quickcreatesequence, // vtiger_field.quickcreatesequence (was $quicksequence in legacy PHP)
        public readonly string  $info_type,           // vtiger_field.info_type
        public readonly string  $fieldparams,         // vtiger_field.fieldparams
        public readonly string  $helpinfo,            // vtiger_field.helpinfo
        public readonly int     $summaryfield,        // vtiger_field.summaryfield
        public readonly ?string $header_field,        // vtiger_field.header_field
        public readonly int     $maxlengthtext,       // vtiger_field.maxlengthtext
        public readonly int     $maxwidthcolumn,      // vtiger_field.maxwidthcolumn
    ) {}

    /**
     * Hydrate from a vtiger_field DB row or any superset thereof (e.g. from
     * App\Fields\Field::getFieldInfo()). Unknown keys are silently ignored.
     * Missing optional keys fall back to their DB DEFAULT values.
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:                  (int) $row['fieldid'],
            tabid:               (int) $row['tabid'],
            name:                (string) $row['fieldname'],
            label:               (string) $row['fieldlabel'],
            table:               (string) $row['tablename'],
            column:              (string) $row['columnname'],
            columntype:          isset($row['columntype']) ? (string) $row['columntype'] : null,
            uitype:              (int) $row['uitype'],
            typeofdata:          (string) ($row['typeofdata'] ?? 'V'),
            displaytype:         (int) ($row['displaytype'] ?? 1),
            generatedtype:       (int) ($row['generatedtype'] ?? 0),    // DB DEFAULT 0
            readonly:            (bool) ($row['readonly'] ?? false),
            mandatory:           (bool) ($row['mandatory'] ?? false),   // DB DEFAULT 0
            presence:            (int) ($row['presence'] ?? 1),         // DB DEFAULT 1
            defaultvalue:        (string) ($row['defaultvalue'] ?? ''),
            maximumlength:       (int) ($row['maximumlength'] ?? 100),
            sequence:            (int) ($row['sequence'] ?? 0),
            block:               isset($row['block']) && $row['block'] !== null ? (int) $row['block'] : null,
            masseditable:        (int) ($row['masseditable'] ?? 1),     // DB DEFAULT 1
            quickcreate:         (int) ($row['quickcreate'] ?? 1),      // DB DEFAULT 1
            quickcreatesequence: isset($row['quickcreatesequence']) && $row['quickcreatesequence'] !== null
                                     ? (int) $row['quickcreatesequence']
                                     : null,
            info_type:           (string) ($row['info_type'] ?? 'BAS'),
            fieldparams:         (string) ($row['fieldparams'] ?? ''),
            helpinfo:            (string) ($row['helpinfo'] ?? ''),
            summaryfield:        (int) ($row['summaryfield'] ?? 0),     // DB DEFAULT 0
            header_field:        isset($row['header_field']) && $row['header_field'] !== null && $row['header_field'] !== ''
                                     ? (string) $row['header_field']
                                     : null,
            maxlengthtext:       (int) ($row['maxlengthtext'] ?? 0),    // DB DEFAULT 0
            maxwidthcolumn:      (int) ($row['maxwidthcolumn'] ?? 0),   // DB DEFAULT 0
        );
    }

    /**
     * Immutable update — returns a new DTO with only the specified fields replaced.
     * Used by Base\Models\Field::updateMandatory() and similar methods to mutate
     * persistence-bound state without breaking the readonly contract.
     */
    public function with(array $changes): self
    {
        $row = $this->toRow();
        foreach ($changes as $property => $value) {
            $rowKey = self::PROPERTY_TO_ROW_KEY[$property] ?? $property;
            $row[$rowKey] = $value;
        }
        return self::fromRow($row);
    }

    /**
     * Produces a write-compatible array for INSERT/UPDATE against vtiger_field.
     * bool properties are cast to int here — the only place in the codebase
     * where the bool→tinyint(1) conversion occurs, keeping the DB layer free
     * of PHP-typed values even under STRICT_TRANS_TABLES.
     */
    public function toRow(): array
    {
        return [
            'fieldid'             => $this->id,
            'tabid'               => $this->tabid,
            'fieldname'           => $this->name,
            'fieldlabel'          => $this->label,
            'tablename'           => $this->table,
            'columnname'          => $this->column,
            'uitype'              => $this->uitype,
            'typeofdata'          => $this->typeofdata,
            'displaytype'         => $this->displaytype,
            'generatedtype'       => $this->generatedtype,
            'readonly'            => (int) $this->readonly,
            'mandatory'           => (int) $this->mandatory,
            'presence'            => $this->presence,
            'defaultvalue'        => $this->defaultvalue,
            'maximumlength'       => $this->maximumlength,
            'sequence'            => $this->sequence,
            'block'               => $this->block,
            'masseditable'        => $this->masseditable,
            'quickcreate'         => $this->quickcreate,
            'quickcreatesequence' => $this->quickcreatesequence,
            'info_type'           => $this->info_type,
            'fieldparams'         => $this->fieldparams,
            'helpinfo'            => $this->helpinfo,
            'summaryfield'        => $this->summaryfield,
            'header_field'        => $this->header_field,
            'maxlengthtext'       => $this->maxlengthtext,
            'maxwidthcolumn'      => $this->maxwidthcolumn,
        ];
    }
}
