{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce.com
********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Settings/LayoutEditor/RelatedList.tpl -->
    <div id="relatedTabOrder">
    <div class="" id="layoutEditorContainer">
        <input id="selectedModuleName" type="hidden" value="{$SELECTED_MODULE_NAME}" />
        <div class="widget_header row">
            <div class="col-md-7">
                {include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
            </div>
            <div class="col-md-5">
				<div class="btn-toolbar">
					<div class="btn-group col-xs-5 pull-right paddingLRZero">
						<select class="select2 form-control layoutEditorRelModules" name="layoutEditorRelModules">
							{foreach item=MODULE_NAME from=$SUPPORTED_MODULES}
								<option value="{$MODULE_NAME}" {if $MODULE_NAME eq $SELECTED_MODULE_NAME} selected {/if}>{$MODULE_NAME|t:$MODULE_NAME}</option>
							{/foreach}
						</select>
					</div>
					{if $CHANGE_RELATIONS_ENABLED}
						<button class="btn btn-primary pull-right addRelation" type="button">{"LBL_ADD_RELATION"|t:$QUALIFIED_MODULE}</button>
					{/if}	
				</div>
            </div>
        </div>
        <hr>
        <div class="relatedTabModulesList">
            {if empty($RELATED_MODULES)}
                <div class="emptyRelatedTabs">
                    <div class="recordDetails">
                        <p class="textAlignCenter">{"LBL_NO_RELATED_INFORMATION"|t:$QUALIFIED_MODULE}</p>
                    </div>
                </div>
            {else}
                <div class="relatedListContainer">	
					<div class="relatedModulesList">
						{foreach item=MODULE_MODEL from=$RELATED_MODULES}
							{assign var=INVENTORY_FIELD_MODEL value=false}
							{assign var=RELATED_MODULE_NAME value=$MODULE_MODEL->getRelationModuleName()}
							{assign var=RELATED_MODULE_MODEL value=$MODULE_MODEL->getRelationModuleModel()}
							{assign var=RECORD_STRUCTURE_INSTANCE value=$RECORD_STRUCTURES[$MODULE_MODEL->getId()]}
							{if $RECORD_STRUCTURE_INSTANCE}
								{assign var=RECORD_STRUCTURE value=$RECORD_STRUCTURE_INSTANCE->getStructure()}
							{else}
								{assign var=RECORD_STRUCTURE value=[]}
							{/if}
							{if $RELATED_MODULE_MODEL && $RELATED_MODULE_MODEL->isInventory()}
								{if isset($INVENTORY_FIELDS[$MODULE_MODEL->getId()])}
									{assign var=INVENTORY_FIELD_MODEL value=$INVENTORY_FIELDS[$MODULE_MODEL->getId()]}
								{/if}
								{assign var=SELECTED_INVENTORY_FIELDS value=$MODULE_MODEL->getRelationInventoryFields()}
							{/if}
							{if $MODULE_MODEL->isActive()}
								{assign var=STATUS value='1'}
							{else}
								{assign var=STATUS value='0'}
							{/if}
							{if isset($SELECTED_FIELDS[$MODULE_MODEL->getId()])}
								{assign var=SELECTED_FIELDS value=$SELECTED_FIELDS[$MODULE_MODEL->getId()]}
							{else}
								{assign var=SELECTED_FIELDS value=[]}
							{/if}
							{assign var=RELATION_SELECTED_FIELDS value=$SELECTED_FIELDS}
							<div class="relatedModule mainBlockTable panel panel-default" data-relation-id="{$MODULE_MODEL->getId()}" data-status="{$STATUS}">
                                <div class="mainBlockTableHeader panel-heading">
									<div class="btn-toolbar btn-group-xs pull-right">
										{if $CHANGE_RELATIONS_ENABLED}
											<button type="button" class="btn btn-danger removeRelation pull-right" title="{"LBL_REMOVE_RELATION"|t:$QUALIFIED_MODULE}">x</button>
										{/if}
										{assign var=FAVORITES value=$MODULE_MODEL->isFavorites()}
			                        	<button type="button" class="btn btn-default addToFavorites" data-state="{$MODULE_MODEL->get('favorites')}">
												<span class="glyphicon glyphicon-star {if !$FAVORITES}hide{/if}" title="{"LBL_DEACTIVATE_FAVORITES"|t:$QUALIFIED_MODULE}"></span>
												<span class="glyphicon glyphicon-star-empty {if $FAVORITES}hide{/if}" title="{"LBL_ACTIVATE_FAVORITES"|t:$QUALIFIED_MODULE}"></span>	
										</button>
			                        	<button type="button" class="btn btn-success inActiveRelationModule{if !$MODULE_MODEL->isActive()} hide{/if}"><span class="glyphicon glyphicon-ok"></span>&nbsp;&nbsp;<strong>{"LBL_VISIBLE"|t:$QUALIFIED_MODULE}</strong></button>
			                        	<button type="button" class="btn btn-warning activeRelationModule{if $MODULE_MODEL->isActive()} hide{/if}"><span class="glyphicon glyphicon-remove"></span>&nbsp;<strong>{"LBL_HIDDEN"|t:$QUALIFIED_MODULE}</strong></button>
			                        </div>
									<h4 class="panel-title">
										<div class="relatedModuleLabel mainBlockTableLabel">
											<a><img src="{vimage_path('drag.png')}" title="{"LBL_DRAG"|t:$QUALIFIED_MODULE}"/></a>
											<strong>{$MODULE_MODEL->get('label')|t:$RELATED_MODULE_NAME}</strong>
										</div>
									</h4>
                                </div>
								<div class="relatedModuleFieldsList mainBlockTableContent panel-body paddingBottomZero">
									<div class="form-group">
									<label class="control-label">{"LBL_STANDARD_FIELDS"|t:$QUALIFIED_MODULE}</label>
										<select data-placeholder="{"LBL_ADD_MORE_COLUMNS"|t:$MODULE}" multiple class="select2_container columnsSelect relatedColumnsList">
				                        	<optgroup label=''>
												{if is_array($RELATION_SELECTED_FIELDS)}
													{foreach item=SELECTED_FIELD from=$RELATION_SELECTED_FIELDS}
														{assign var=FIELD_INSTANCE value=$RELATED_MODULE_MODEL->getField($SELECTED_FIELD)}
														{if $FIELD_INSTANCE}
															<option value="{$FIELD_INSTANCE->getId()}" data-name="{$FIELD_INSTANCE->getFieldName()}" selected>
																{$FIELD_INSTANCE->get('label')|t:$RELATED_MODULE_NAME}
													  		</option>
												  		{/if}
													{/foreach}
												{/if}
											</optgroup>
					                        {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
												<optgroup label='{$BLOCK_LABEL|t:$RELATED_MODULE_NAME}'>
													{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
														{if !is_array($RELATION_SELECTED_FIELDS) || !in_array($FIELD_MODEL->getId(), $RELATION_SELECTED_FIELDS)}
															<option value="{$FIELD_MODEL->getId()}" data-field-name="{$FIELD_NAME}">
																{$FIELD_MODEL->get('label')|t:$RELATED_MODULE_NAME}
													  		</option>
												  		{/if}
													{/foreach}
												</optgroup>
						                    {/foreach}
                     					</select>
                    				</div>
									{if $INVENTORY_FIELD_MODEL}
										<div class="form-group">
										<label class="control-label">{"LBL_ADVANCED_BLOCK_FIELDS"|t:$QUALIFIED_MODULE}</label>
											<select data-placeholder="{"LBL_ADD_ADVANCED_BLOCK_FIELDS"|t:$QUALIFIED_MODULE}" multiple class="select2_container relatedColumnsList" data-type="inventory">
												{foreach item=NAME key=SELECTED_FIELD from=$SELECTED_INVENTORY_FIELDS}
													{assign var=FIELD_INSTANCE value=$INVENTORY_FIELDS[$SELECTED_FIELD]}
													{if $FIELD_INSTANCE}
														<option value="{$FIELD_INSTANCE->getColumnName()}" data-name="{$FIELD_INSTANCE->getColumnName()}" selected>
															{$FIELD_INSTANCE->get('label')|t:$RELATED_MODULE_NAME}
														</option>
													{/if}
												{/foreach}
												{foreach item=FIELD_MODEL from=$INVENTORY_FIELDS}
													{if !is_array($SELECTED_INVENTORY_FIELDS) || !in_array($FIELD_MODEL->getColumnName(), $SELECTED_INVENTORY_FIELDS)}
														<option value="{$FIELD_MODEL->getColumnName()}" data-field-name="{$FIELD_MODEL->getColumnName()}">
															{$FIELD_MODEL->get('label')|t:$RELATED_MODULE_NAME}
														</option>
													{/if}
												{/foreach}
											</select>
										</div>
									{/if}
								</div>
							</div>
						{/foreach}
					</div>
				</div>
            {/if}
        </div>
        </div>
		<div class="addRelationContainer modal fade" tabindex="-1">	
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
						<h3 id="myModalLabel" class="modal-title">{"LBL_ADD_RELATION"|t:$QUALIFIED_MODULE}</h3>
					</div>
					<div class="modal-body" >
						<form class="modal-Fields">
							<div class="row form-horizontal">
								<div class="form-group">
									<label class="col-md-4 control-label">{"LBL_RELATION_TYPE"|t:$QUALIFIED_MODULE}:</label>
									<div class="col-md-7">
										<select name="type" class="form-control">
											{foreach from=$RELATIONS_TYPES item=ITEM key=KEY}
												<option value="{$KEY}">{$ITEM|t:$QUALIFIED_MODULE}</option>
											{/foreach}
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-4 control-label">{"LBL_RELATION_ACTIONS"|t:$QUALIFIED_MODULE}:</label>
									<div class="col-md-7 marginTop">
										<select multiple name="actions" class="form-control">
											{foreach from=$RELATIONS_ACTIONS item=ITEM key=KEY}
												<option value="{$KEY}">{$ITEM|t:$QUALIFIED_MODULE}</option>
											{/foreach}
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-4 control-label">{"LBL_SOURCE_MODULE"|t:$QUALIFIED_MODULE}:</label>
									<div class="col-md-7 marginTop">
										<select name="source" class="form-control">
											{foreach item=MODULE_NAME from=$SUPPORTED_MODULES}
												<option value="{$MODULE_NAME}" {if $MODULE_NAME eq $SELECTED_MODULE_NAME} selected {/if}>{$MODULE_NAME|t:$MODULE_NAME}</option>
											{/foreach}
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-4 control-label">{"LBL_TARGET_MODULE"|t:$QUALIFIED_MODULE}:</label>
									<div class="col-md-7 marginTop">
										<select name="target" class="target form-control">
											{foreach item=MODULE_NAME from=$SUPPORTED_MODULES}
												<option value="{$MODULE_NAME}">{$MODULE_NAME|t:$MODULE_NAME}</option>
											{/foreach}
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-md-4 control-label">{"LBL_RELATION_LABLE"|t:$QUALIFIED_MODULE}:</label>
									<div class="col-md-7">
										<input name="label"  type="text" class="relLabel form-control"/>
									</div>
								</div>
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn btn-success addButton" data-dismiss="modal" aria-hidden="true" >{"LBL_SAVE"|t:$QUALIFIED_MODULE}</button>
						<button class="btn btn-warning" id="closeModal" data-dismiss="modal" aria-hidden="true">{"LBL_CLOSE"|t:$QUALIFIED_MODULE}</button>
					</div>
				</div>	
			</div>	
		</div>	
    </div>	
<!--/layouts/basic/modules/Settings/LayoutEditor/RelatedList.tpl -->
{/strip}
