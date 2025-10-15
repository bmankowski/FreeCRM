{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/OSSMail/MailActionBarRow.tpl -->
	<div class="rowRelatedRecord" data-id="{$RELATED['id']}" data-module="{$RELATED['module']}">
		<a href="{$URL}index.php?module={$RELATED['module']}&amp;view=Detail&amp;record={$RELATED['id']}" title="{'SINGLE_'|cat:$RELATED['module']|t:$RELATED['module']}: {$RELATED['label']}" target="_blank">
			<span class="relatedModuleIcon userIcon-{$RELATED['module']}" aria-hidden="true"></span>
			<span class="relatedName">
				{vtlib\Functions::textLength($RELATED['label'],38)}
			</span>
		</a>
		<div class="pull-right rowActions">
			{if Users_Privileges_Model::isPermitted('Calendar','CreateView')}
				<button class="addRelatedRecord" data-module="Calendar" title="{"LBL_ADD_CALENDAR"|t:$MODULE_NAME}">
					<span class="userIcon-Calendar" aria-hidden="true"></span>
				</button>
			{/if}
			{if Users_Privileges_Model::isPermitted('ModComments','CreateView')}
				<button class="addRelatedRecord" data-module="ModComments" title="{"LBL_ADD_MODCOMMENTS"|t:$MODULE_NAME}">
					<span class="glyphicon glyphicon-comment" aria-hidden="true"></span>
				</button>
			{/if}
			{if in_array($RELATED['module'], ['HelpDesk','Project']) &&  Users_Privileges_Model::isPermitted('HelpDesk','CreateView')}
				<button class="addRelatedRecord" data-module="HelpDesk" title="{"LBL_ADD_HELPDESK"|t:$MODULE_NAME}">
					<span class="userIcon-HelpDesk" aria-hidden="true"></span>
				</button>
			{/if}
			{if in_array($RELATED['module'], ['Accounts','Contacts','Leads']) && Users_Privileges_Model::isPermitted('Products','DetailView')}
				<button class="selectRecord" data-type="1" data-module="Products" title="{"LBL_ADD_PRODUCTS"|t:$MODULE_NAME}">
					<span class="userIcon-Products" aria-hidden="true"></span>
				</button>
			{/if}
			{if in_array($RELATED['module'], ['Accounts','Contacts','Leads']) &&  Users_Privileges_Model::isPermitted('Services','DetailView')}
				<button class="selectRecord" data-type="1" data-module="Services" title="{"LBL_ADD_SERVICES"|t:$MODULE_NAME}">
					<span class="userIcon-Services" aria-hidden="true"></span>
				</button>
			{/if}
			<button class="removeRecord " title="{"LBL_REMOVE_RELATION"|t:$MODULE_NAME} {$RELATED['label']}">
				<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
			</button>
		</div>
	</div>
<!--/layouts/basic/modules/OSSMail/MailActionBarRow.tpl -->
{/strip}
