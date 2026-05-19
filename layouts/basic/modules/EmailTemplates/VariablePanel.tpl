{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
	<!-- layouts/basic/modules/EmailTemplates/VariablePanel.tpl -->
	<div class="row">
		{include file='layouts/basic/modules/Base/VariablePanel.tpl' SELECTED_MODULE=$SELECTED_MODULE PARSER_TYPE=$PARSER_TYPE GRAY=$GRAY TEXT_PARSER=$TEXT_PARSER VARIABLE_PANEL_HAS_ENTITY_INFO=$VARIABLE_PANEL_HAS_ENTITY_INFO QUALIFIED_SETTINGS_MODULE=$QUALIFIED_SETTINGS_MODULE VARIABLE_PANEL_DYNAMIC_ALIASES=$VARIABLE_PANEL_DYNAMIC_ALIASES}
	</div>
	<input type="hidden" class="js-dynamic-elements-json" value="{$DYNAMIC_ELEMENTS_JSON}">
	<!--/layouts/basic/modules/EmailTemplates/VariablePanel.tpl -->
{/strip}
