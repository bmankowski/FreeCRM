{strip}
<!-- layouts/basic/modules/Settings/AdvancedPermission/DetailView.tpl -->
	{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
	<div class="widget_header row">
		<div class="col-md-8">
			{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
			{if isset($SELECTED_PAGE)}
				{$SELECTED_PAGE->get('description')|t:$QUALIFIED_MODULE}
			{/if}
		</div>
		<div class="col-md-4 ">
			<a href="{$RECORD_MODEL->getEditViewUrl()}" class="btn btn-info pull-right">
				<strong>{"LBL_EDIT_RECORD"|t:$QUALIFIED_MODULE}</strong>
			</a>
		</div>
	</div>
	<div class="detailViewInfo" id="groupsDetailContainer">
		<div class="">
			<form id="detailView" class="form-horizontal" method="POST">
				<div class="form-group">
					<div class="col-md-2 text-right">
						{"LBL_NAME"|t:$QUALIFIED_MODULE} 
					</div>
					<div class="col-md-10">
						<strong>{$RECORD_MODEL->getName()}</strong>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 text-right">
						{"LBL_ACTION"|t:$QUALIFIED_MODULE}  
					</div>
					<div class="col-md-10">
						<strong>{$RECORD_MODEL->getDisplayValue('action')|t:$QUALIFIED_MODULE}</strong>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 text-right">
						{"LBL_STATUS"|t:$QUALIFIED_MODULE}  
					</div>
					<div class="col-md-10">
						<strong>{$RECORD_MODEL->getDisplayValue('status')|t:$QUALIFIED_MODULE}</strong>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 text-right">
						{"LBL_PRIORITY"|t:$QUALIFIED_MODULE}  
					</div>
					<div class="col-md-10">
						<strong>{$RECORD_MODEL->getDisplayValue('priority')|t}</strong>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 text-right">
						{"LBL_MODULE"|t:$QUALIFIED_MODULE}  
					</div>
					<div class="col-md-10">
						<strong>{$RECORD_MODEL->getDisplayValue('tabid')|t:$RECORD_MODEL->getDisplayValue('tabid')}</strong>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 text-right">
						{"LBL_MEMBERS"|t:$QUALIFIED_MODULE}  
					</div>
					<div class="col-md-10">
						<strong>{$RECORD_MODEL->getDisplayValue('members')}</strong>
					</div>
				</div>
				<div class="form-group">
					<div class="col-md-2 text-right">
						{"LBL_USERS"|t:$QUALIFIED_MODULE}  
					</div>
					<div class="col-md-10">
						{foreach from=$RECORD_MODEL->getUserByMember() item=NAME}
							<div><strong>{$NAME}</strong></div>
						{/foreach}
					</div>
				</div>
			</form>
		</div>
	</div>
	{strip}
