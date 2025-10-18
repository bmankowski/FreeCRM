<?php
/* +**********************************************************************************
 * Global Class Aliases for Legacy Code Compatibility
 * This file provides global aliases for commonly used classes in legacy code
 * ********************************************************************************** */

// Create global class aliases using spl_autoload_register to avoid circular dependencies
spl_autoload_register(function ($class) {
	$aliases = [
		// Users models - used in .tpl files
		'Users_Record_Model' => 'App\Modules\Users\Models\Record',
		'Users_Privileges_Model' => 'App\Modules\Users\Models\Privileges',
		'Users_Colors_Model' => 'App\Modules\Users\Models\Colors',
		// ModComments models - used in .tpl files
		'ModComments_Record_Model' => 'App\Modules\ModComments\Models\Record',
		// ModTracker models - used in .tpl files
		'ModTracker_Record_Model' => 'App\Modules\ModTracker\Models\Record',
		// Vtiger base models - used in .tpl files
		'Vtiger_Module_Model' => 'App\Modules\Vtiger\Models\Module',
		'Vtiger_Record_Model' => 'App\Modules\Vtiger\Models\Record',
		'Vtiger_Field_Model' => 'App\Modules\Vtiger\Models\Field',
		'Vtiger_Menu_Model' => 'App\Modules\Vtiger\Models\Menu',
		'Vtiger_PDF_Model' => 'App\Modules\Vtiger\Models\PDF',
		'Vtiger_RecordStructure_Model' => 'App\Modules\Vtiger\Models\RecordStructure',
		'Vtiger_RelationListView_Model' => 'App\Modules\Vtiger\Models\RelationListView',
		'Vtiger_Watchdog_Model' => 'App\Modules\Vtiger\Models\Watchdog',
		// Helpers - used in .tpl files
		'Vtiger_Util_Helper' => 'App\Modules\Vtiger\Util',
		// Settings modules - used in .tpl files
		'Settings_AdvancedPermission_Module_Model' => 'App\Modules\Settings\AdvancedPermission\Models\Module',
		'Settings_Calendar_Module_Model' => 'App\Modules\Settings\Calendar\Models\Module',
		'Settings_Companies_Module_Model' => 'App\Modules\Settings\Companies\Models\Module',
		'Settings_ConfReport_Module_Model' => 'App\Modules\Settings\ConfReport\Models\Module',
		'Settings_DataAccess_Module_Model' => 'App\Modules\Settings\DataAccess\Models\Module',
		'Settings_Groups_Member_Model' => 'App\Modules\Settings\Groups\Models\Member',
		'Settings_Inventory_Module_Model' => 'App\Modules\Settings\Inventory\Models\Module',
		'Settings_LayoutEditor_Module_Model' => 'App\Modules\Settings\LayoutEditor\Models\Module',
		'Settings_MailSmtp_Module_Model' => 'App\Modules\Settings\MailSmtp\Models\Module',
		'Settings_Mail_Config_Model' => 'App\Modules\Settings\Mail\Models\Config',
		'Settings_ModuleManager_Library_Model' => 'App\Modules\Settings\ModuleManager\Models\Library',
		'Settings_ModuleManager_Module_Model' => 'App\Modules\Settings\ModuleManager\Models\Module',
		'Settings_PBXManager_Module_Model' => 'App\Modules\Settings\PBXManager\Models\Module',
		'Settings_PDF_Module_Model' => 'App\Modules\Settings\PDF\Models\Module',
		'Settings_Profiles_Module_Model' => 'App\Modules\Settings\Profiles\Models\Module',
		'Settings_RecordAllocation_Module_Model' => 'App\Modules\Settings\RecordAllocation\Models\Module',
		'Settings_Roles_Record_Model' => 'App\Modules\Settings\Roles\Models\Record',
		'Settings_SMSNotifier_ProviderField_Model' => 'App\Modules\Settings\SMSNotifier\Models\ProviderField',
		'Settings_Vtiger_Icons_Model' => 'App\Modules\Settings\Vtiger\Models\Icons',
		'Settings_Vtiger_Policy_View' => 'App\Modules\Settings\Vtiger\Views\Policy',
		'Settings_WebserviceApps_Module_Model' => 'App\Modules\Settings\WebserviceApps\Models\Module',
		'Settings_WidgetsManagement_Module_Model' => 'App\Modules\Settings\WidgetsManagement\Models\Module',
		'Settings_Workflows_Module_Model' => 'App\Modules\Settings\Workflows\Models\Module',
		// UITypes - used in .tpl files
		'Vtiger_Date_UIType' => 'App\Modules\Vtiger\UiTypes\Date',
		'Vtiger_Datetime_UIType' => 'App\Modules\Vtiger\UiTypes\Datetime',
		'Vtiger_MultiReferenceValue_UIType' => 'App\Modules\Vtiger\UiTypes\MultiReferenceValue',
		// Database - used globally
		'PearDatabase' => 'App\Database\PearDatabase',
	];
	
	if (isset($aliases[$class]) && class_exists($aliases[$class])) {
		class_alias($aliases[$class], $class);
		return true;
	}
	return false;
});

