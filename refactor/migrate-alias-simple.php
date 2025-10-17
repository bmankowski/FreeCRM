#!/usr/bin/env php
<?php
/**
 * Migrate class aliases to use statements - Simplified version
 * Usage: php refactor/migrate-alias-simple.php <AliasName>
 */

if ($argc < 2) {
    echo "Usage: php migrate-alias-simple.php <AliasName>\n";
    exit(1);
}

$aliasName = $argv[1    ];

// Map of aliases to their full namespace paths
$aliasMap = [
    // Batch 1 (completed)
    'Vtiger_Link_Model' => 'App\Modules\Vtiger\Models\Link',
    'Vtiger_ListView_Model' => 'App\Modules\Vtiger\Models\ListView',
    'Users_Module_Model' => 'App\Modules\Users\Models\Module',
    'Vtiger_Action_Model' => 'App\Modules\Vtiger\Models\Action',
    'Vtiger_Block_Model' => 'App\Modules\Vtiger\Models\Block',
    'Vtiger_Relation_Model' => 'App\Modules\Vtiger\Models\Relation',
    // Batch 2 (recommended)
    'Settings_Vtiger_Module_Model' => 'App\Modules\Settings\Vtiger\Models\Module',
    'VTWorkflowManager' => 'App\Modules\com_vtiger_workflow\VTWorkflowManager',
    'Settings_Vtiger_Record_Model' => 'App\Modules\Settings\Vtiger\Models\Record',
    'Vtiger_Paging_Model' => 'App\Modules\Vtiger\Models\Paging',
    'Vtiger_DetailView_Model' => 'App\Modules\Vtiger\Models\DetailView',
    'Vtiger_DependencyPicklist' => 'App\Modules\PickList\DependencyPicklist',
    'VTJsonCondition' => 'App\Modules\com_vtiger_workflow\VTJsonCondition',
    'Vtiger_TreeCategoryModal_Model' => 'App\Modules\Vtiger\Models\TreeCategoryModal',
    'Vtiger_Utility_Model' => 'App\Modules\Vtiger\Models\Utility',
    'Vtiger_TreeView_Model' => 'App\Modules\Vtiger\Models\TreeView',
    'Vtiger_DashBoard_Model' => 'App\Modules\Vtiger\Models\DashBoard',
    // Batch 3
    'Vtiger_JsScript_Model' => 'App\Runtime\Vtiger_JsScript_Model',
    'Vtiger_CRMEntity' => 'App\CRMEntity',
    'Vtiger_CssScript_Model' => 'App\Runtime\Vtiger_CssScript_Model',
    'ModTracker_ModTrackerHandler_Handler' => 'App\Modules\ModTracker\Handlers\Handler',
    'Vtiger_Base_UIType' => 'App\Modules\Vtiger\UiTypes\Base',
    'Vtiger_Workflow_Handler' => 'App\Modules\com_vtiger_workflow\VTWorkflowEventHandler',
    'Vtiger_ModTracker_Model' => 'App\Modules\ModTracker\Models\ModTracker',
    // Batch 4 - Settings modules
    'Settings_Groups_Record_Model' => 'App\Modules\Settings\Groups\Models\Record',
    'Settings_Currency_Record_Model' => 'App\Modules\Settings\Currency\Models\Record',
    'Settings_CronTasks_Record_Model' => 'App\Modules\Settings\CronTasks\Models\Record',
    'Settings_PDF_Record_Model' => 'App\Modules\Settings\PDF\Models\Record',
    'Settings_AdvancedPermission_Record_Model' => 'App\Modules\Settings\AdvancedPermission\Models\Record',
    'Settings_BruteForce_Module_Model' => 'App\Modules\Settings\BruteForce\Models\Module',
    'Settings_MailSmtp_Record_Model' => 'App\Modules\Settings\MailSmtp\Models\Record',
    'Settings_Companies_Record_Model' => 'App\Modules\Settings\Companies\Models\Record',
    'Settings_Currency_Module_Model' => 'App\Modules\Settings\Currency\Models\Module',
    'Settings_CronTasks_Module_Model' => 'App\Modules\Settings\CronTasks\Models\Module',
    'Settings_Mail_Record_Model' => 'App\Modules\Settings\Mail\Models\Record',
    'Settings_Mail_Module_Model' => 'App\Modules\Settings\Mail\Models\Module',
    'Settings_Groups_Module_Model' => 'App\Modules\Settings\Groups\Models\Module',
    // Batch 5 - More Settings
    'Settings_Workflows_Record_Model' => 'App\Modules\Settings\Workflows\Models\Record',
    'Settings_MappedFields_Module_Model' => 'App\Modules\Settings\MappedFields\Models\Module',
    'Settings_SharingAccess_Module_Model' => 'App\Modules\Settings\SharingAccess\Models\Module',
    'Settings_AutomaticAssignment_Record_Model' => 'App\Modules\Settings\AutomaticAssignment\Models\Record',
    'Settings_Picklist_Module_Model' => 'App\Modules\Settings\Picklist\Models\Module',
    'Settings_SharingAccess_Rule_Model' => 'App\Modules\Settings\SharingAccess\Models\Rule',
    'Settings_AutomaticAssignment_Module_Model' => 'App\Modules\Settings\AutomaticAssignment\Models\Module',
    'Settings_TreesManager_Record_Model' => 'App\Modules\Settings\TreesManager\Models\Record',
    'Settings_Leads_Mapping_Model' => 'App\Modules\Settings\Leads\Models\Mapping',
    'Settings_WebserviceUsers_Record_Model' => 'App\Modules\Settings\WebserviceUsers\Models\Record',
    'Settings_PickListDependency_Record_Model' => 'App\Modules\Settings\PickListDependency\Models\Record',
    'Settings_PickListDependency_Module_Model' => 'App\Modules\Settings\PickListDependency\Models\Module',
    'Settings_Leads_Module_Model' => 'App\Modules\Settings\Leads\Models\Module',
    'Settings_WebserviceUsers_Module_Model' => 'App\Modules\Settings\WebserviceUsers\Models\Module',
    'Settings_WebserviceApps_Record_Model' => 'App\Modules\Settings\WebserviceApps\Models\Record',
    // Batch 6 - More Settings
    'Settings_LangManagement_Module_Model' => 'App\Modules\Settings\LangManagement\Models\Module',
    'Settings_CurrencyUpdate_Module_Model' => 'App\Modules\Settings\CurrencyUpdate\Models\Module',
    'Settings_MappedFields_Field_Model' => 'App\Modules\Settings\MappedFields\Models\Field',
    'Settings_LayoutEditor_Field_Model' => 'App\Modules\Settings\LayoutEditor\Models\Field',
    'Settings_Menu_Record_Model' => 'App\Modules\Settings\Menu\Models\Record',
    'Settings_CustomView_Module_Model' => 'App\Modules\Settings\CustomView\Models\Module',
    'Settings_Inventory_Record_Model' => 'App\Modules\Settings\Inventory\Models\Record',
    'Settings_Menu_Module_Model' => 'App\Modules\Settings\Menu\Models\Module',
    'Settings_HideBlocks_Record_Model' => 'App\Modules\Settings\HideBlocks\Models\Record',
    'Settings_LayoutEditor_Block_Model' => 'App\Modules\Settings\LayoutEditor\Models\Block',
    'Settings_Leads_Field_Model' => 'App\Modules\Settings\Leads\Models\Field',
    '\App\Modules\Settings\MarketingProcesses\Models\Module' => 'App\Modules\Settings\MarketingProcesses\Models\Module',
    'Settings_CurrencyUpdate_AbstractBank_Model' => 'App\Modules\Settings\CurrencyUpdate\Models\AbstractBank',
    'Settings_Github_Client_Model' => 'App\Modules\Settings\Github\Models\Client',
    'Settings_ApiAddress_Module_Model' => 'App\Modules\Settings\ApiAddress\Models\Module',
