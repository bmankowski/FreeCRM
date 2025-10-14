{*<!--
/*+***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 *************************************************************************************************************************************/
-->*}
<div class="" id="menuEditorContainer">
    <div class="widget_header row">
        <div class="col-md-12">
	    {include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
	</div>
    </div>
    <div id="my-tab-content" class="tab-content" style="margin: 0 20px;" >
        <div class='editViewContainer' id="tpl" style="min-height:300px">
            <div class="row">
                <div class="col-md-4 paddingLRZero btn-toolbar">
                    <a class="btn btn-default addButton" href="index.php?module={$MODULE_NAME}&parent=Settings&view=Step1">
                        <strong>{"LBL_NEW_TPL"|t:$QUALIFIED_MODULE}</strong>
                    </a>
                </div>
                <div class="col-md-3 paddingLRZero btn-toolbar marginLeftZero" >
                    <select class="chzn-select form-control" id="moduleFilter" style="margin-left:5px;" >
                        <option value="">{"LBL_CONDITION_ALL"|t:$QUALIFIED_MODULE}</option>
                        {foreach item=item key=key from=$SUPPORTED_MODULE_MODELS}
                            <option value="{$item}">{vtranslate($item, $item)}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <br>
            <div class="row" id="list_doc">
                <table class="table table-bordered table-condensed listViewEntriesTable">
                    <thead>
                        <tr class="listViewHeaders" >
                            <th width="30%">{"LBL_MODULE_NAME"|t:$QUALIFIED_MODULE}</th>
                            <th>{"DOC_NAME"|t:$QUALIFIED_MODULE}</th>
                            <th colspan="2"></th>
                        </tr>
                    </thead>
                    {if !empty($DOC_TPL_LIST)}

                    <tbody>
                        {foreach from=$DOC_TPL_LIST item=item key=key}
                        <tr class="listViewEntries" data-id="{$item.id}">
                                <td onclick="location.href = jQuery(this).data('url')" data-url="index.php?module={$MODULE_NAME}&parent=Settings&view=Step1&tpl_id={$item.id}">{vtranslate($item.module, $item.module)}</td>
                                <td onclick="location.href = jQuery(this).data('url')" data-url="index.php?module={$MODULE_NAME}&parent=Settings&view=Step1&tpl_id={$item.id}"> {vtranslate($item.summary, $QUALIFIED_MODULE)}</td>
                                <td><a class="pull-right edit_tpl" href="index.php?module={$MODULE_NAME}&parent=Settings&view=Step1&tpl_id={$item.id}"><!--<span title="{"LBL_EDIT"|t}" class="glyphicon glyphicon-pencil alignMiddle"></span>--></a>
                                    <a href='index.php?module={$MODULE_NAME}&parent=Settings&action=DeleteTemplate&tpl_id={$item.id}' class="pull-right marginRight10px">
                                        <span type="{"REMOVE_TPL"|t:$QUALIFIED_MODULE}" class="glyphicon glyphicon-trash alignMiddle"></span></a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
                {else}
                    <table class="emptyRecordsDiv">
                        <tbody>
                            <tr>
                                <td>
                                    {"LBL_NO_TPL_ADDED"|t:$QUALIFIED_MODULE}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                {/if}
            </div>
        </div>
    </div>
</div>
