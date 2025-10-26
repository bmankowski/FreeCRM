<?php
/* +**********************************************************************************
 * Legacy Class Aliases for PSR-4 Migration
 * This file provides backward compatibility for migrated classes
 * Generated automatically by PSR-4 Migration Script v2.0
 * ********************************************************************************** */

// Core aliases
if (!class_exists('Vtiger_WebUI')) {
    class_alias('Vtiger\Core\WebUI', 'Vtiger_WebUI');
}

if (!class_exists('Vtiger_EntryPoint')) {
    class_alias('Vtiger\Core\EntryPoint', 'Vtiger_EntryPoint');
}

if (!class_exists('Vtiger_Session')) {
    class_alias('Vtiger\Http\Session', 'Vtiger_Session');
}

if (!class_exists('Vtiger_Request')) {
    class_alias('Vtiger\Http\Request', 'Vtiger_Request');
}

if (!class_exists('Vtiger_Response')) {
    class_alias('Vtiger\Http\Response', 'Vtiger_Response');
}

if (!class_exists('Vtiger_Loader')) {
    class_alias('App\Loader', 'Vtiger_Loader');
}

if (!class_exists('Vtiger_Language_Handler')) {
    class_alias('Vtiger\Language\Handler', 'Vtiger_Language_Handler');
}

if (!class_exists('FreeCRM_Viewer')) {
    class_alias('Vtiger\Runtime\Viewer', 'FreeCRM_Viewer');
}

if (!class_exists('Vtiger_Theme')) {
    class_alias('Vtiger\Runtime\Theme', 'Vtiger_Theme');
}

if (!class_exists('Vtiger_Controller')) {
    class_alias('Vtiger\Runtime\Controller', 'Vtiger_Controller');
}

if (!class_exists('BaseModel')) {
    class_alias('Vtiger\Core\Models\BaseModel', 'BaseModel');
}

if (!class_exists('Vtiger_Record_Model')) {
    class_alias('Vtiger\Core\Models\RecordModel', 'Vtiger_Record_Model');
}

if (!class_exists('Vtiger_Module_Model')) {
    class_alias('Vtiger\Core\Models\ModuleModel', 'Vtiger_Module_Model');
}

if (!class_exists('Vtiger_Field_Model')) {
    class_alias('Vtiger\Core\Models\FieldModel', 'Vtiger_Field_Model');
}

// Entity aliases
if (!class_exists('CRMEntity')) {
    class_alias('Vtiger\Core\Entity\CRMEntity', 'CRMEntity');
}

if (!class_exists('Users')) {
    class_alias('Vtiger\Modules\Users\Users', 'Users');
}

if (!class_exists('Reports')) {
    class_alias('Vtiger\Modules\Reports\Reports', 'Reports');
}

if (!class_exists('Calendar')) {
    class_alias('Vtiger\Modules\Calendar\Calendar', 'Calendar');
}

if (!class_exists('Leads')) {
    class_alias('Vtiger\Modules\Leads\Leads', 'Leads');
}

if (!class_exists('Accounts')) {
    class_alias('Vtiger\Modules\Accounts\Accounts', 'Accounts');
}

if (!class_exists('Contacts')) {
    class_alias('Vtiger\Modules\Contacts\Contacts', 'Contacts');
}

if (!class_exists('HelpDesk')) {
    class_alias('Vtiger\Modules\HelpDesk\HelpDesk', 'HelpDesk');
}

if (!class_exists('Documents')) {
    class_alias('Vtiger\Modules\Documents\Documents', 'Documents');
}

if (!class_exists('Products')) {
    class_alias('Vtiger\Modules\Products\Products', 'Products');
}

if (!class_exists('Campaigns')) {
    class_alias('Vtiger\Modules\Campaigns\Campaigns', 'Campaigns');
}

// Utility aliases
if (!class_exists('EmailTemplate')) {
    class_alias('Vtiger\Utils\EmailTemplate', 'EmailTemplate');
}

if (!class_exists('LanguageTranslator')) {
    class_alias('Vtiger\Language\Translator', 'LanguageTranslator');
}

if (!class_exists('AppConfig')) {
    class_alias('Vtiger\Config\AppConfig', 'AppConfig');
}

if (!class_exists('PearDatabase')) {
    class_alias('Vtiger\Database\PearDatabase', 'PearDatabase');
}

