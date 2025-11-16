{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/OSSMail/MailActionBar.tpl -->
	{if !$RECORD}
		<input type="hidden" id="mailActionBarID" value="" />
		<div class="noRecords">
			{"LBL_MAIL_NOT_FOUND_IN_DB"|t:$MODULE_NAME} <a class="importMail">{"LBL_IMPORT_MAIL_MANUALLY"|t:$MODULE_NAME}</a>
		</div>
	{else}
		<input type="hidden" id="mailActionBarID" value="{$RECORD}" />
		{assign var="MODULES_LEVEL_0" value=\App\Core\ModuleHierarchy::getModulesByLevel()}
		{assign var="MODULES_LEVEL_1" value=\App\Core\ModuleHierarchy::getModulesByLevel(1)}
		{assign var="MODULES_LEVEL_2" value=\App\Core\ModuleHierarchy::getModulesByLevel(2)}
		{if !empty($MODULES_LEVEL_0)}
			<input type="hidden" id="modulesLevel0" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode(array_keys($MODULES_LEVEL_0)))}" />
		{/if}
		{if !empty($MODULES_LEVEL_1)}
			<input type="hidden" id="modulesLevel1" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode(array_keys($MODULES_LEVEL_1)))}" />
		{/if}
		{if !empty($MODULES_LEVEL_2)}
			<input type="hidden" id="modulesLevel2" value="{\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode(array_keys($MODULES_LEVEL_2)))}" />
		{/if}
		<div class="row actionBar">
			<div class="col-4" >
				<div class="head row">
					{if !empty($MODULES_LEVEL_0)}
						<div data-type="link">
							<div class="col">
								{"LBL_RELATIONS"|t:$MODULE_NAME}
								<div class="pull-right">
									{assign var="ACCESS_LEVEL_0" value=\App\Core\ModuleHierarchy::accessModulesByLevel()}
									{if $ACCESS_LEVEL_0}
										<select class="module">
											{foreach item="ITEM" key="MODULE" from=$ACCESS_LEVEL_0}
												<option value="{$MODULE}">
													{$MODULE|t:$MODULE}
												</option>
											{/foreach}
										</select>
										<button class="addRecord" title="{"LBL_ADD_RECORD"|t:$MODULE_NAME}">
											<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
										</button>
									{/if}
									{if \App\Core\ModuleHierarchy::accessModulesByLevel(0,'DetailView')}
										<button class="selectRecord" data-type="0" title="{"LBL_SELECT_RECORD"|t:$MODULE_NAME}">
											<span class="glyphicon glyphicon-resize-small" aria-hidden="true"></span>
										</button>
									{/if}
								</div>
							</div>
						</div>
					{/if}
				</div>
				<div class="data row">
					{if !empty($MODULES_LEVEL_0)}
						<div data-type="link">
							<div class="col">
								{foreach key=MODULE item=ITEM from=$MODULES_LEVEL_0}
									{foreach item=RELATED from=$RELATED_RECORDS[$MODULE]}
										{include file='MailActionBarRow.tpl'|@vtemplate_path:$MODULE_NAME}
									{/foreach}
								{/foreach}
							</div>
						</div>
					{/if}
				</div>
			</div>
			<div class="col-4">
				<div class="head row">
					{if !empty($MODULES_LEVEL_1)}
						<div data-type="process">
							<div class="col">
								{"LBL_PROCESS"|t:$MODULE_NAME}
								<div class="pull-right">
									{assign var="ACCESS_LEVEL_1" value=\App\Core\ModuleHierarchy::accessModulesByLevel(1)}
									{if $ACCESS_LEVEL_1}
										<select class="module">
											{foreach item="ITEM" key="MODULE" from=$ACCESS_LEVEL_1}
												<option value="{$MODULE}">
													{$MODULE|t:$MODULE}
												</option>
											{/foreach}
										</select>
										<button class="addRecord" title="{"LBL_ADD_RECORD"|t:$MODULE_NAME}">
											<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
										</button>
									{/if}
									{if \App\Core\ModuleHierarchy::accessModulesByLevel(1,'DetailView')}
										<button class="selectRecord" data-type="0" title="{"LBL_SELECT_RECORD"|t:$MODULE_NAME}">
											<span class="glyphicon glyphicon-resize-small" aria-hidden="true"></span>
										</button>
									{/if}
								</div>
							</div>
						</div>
					{/if}
				</div>
				<div class="data row">
					{if !empty($MODULES_LEVEL_1)}
						<div data-type="link">
							<div class="col">
								{foreach key=MODULE item=ITEM from=$MODULES_LEVEL_1}
									{foreach item=RELATED from=$RELATED_RECORDS[$MODULE]}
										{include file='MailActionBarRow.tpl'|@vtemplate_path:$MODULE_NAME}
									{/foreach}
								{/foreach}
							</div>
						</div>
					{/if}
				</div>
			</div>	
			<div class="col-4">
				<div class="head row">	
					{if !empty($MODULES_LEVEL_2)}
						<div data-type="subprocess">
							<div class="col">
								{"LBL_SUB_PROCESS"|t:$MODULE_NAME}
								<div class="pull-right">
									{assign var="ACCESS_LEVEL_2" value=\App\Core\ModuleHierarchy::accessModulesByLevel(2)}
									{if $ACCESS_LEVEL_2}
										<select class="module">
											{foreach item="ITEM" key="MODULE" from=\App\Core\ModuleHierarchy::accessModulesByLevel(1)}
												{assign var="ACCESS_PARENT" value=\App\Core\ModuleHierarchy::accessModulesByParent($MODULE)}
												{if $ACCESS_PARENT}
													<optgroup label="{$MODULE|t:$MODULE}">
														{foreach item="PARENT_ITEM" key="PARENT_MODULE" from=$ACCESS_PARENT}
															<option value="{$PARENT_MODULE}">
																{$PARENT_MODULE|t:$PARENT_MODULE}
															</option>
														{/foreach}
													</optgroup>
												{/if}
											{/foreach}
										</select>
										<button class="addRecord" title="{"LBL_ADD_RECORD"|t:$MODULE_NAME}">
											<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
										</button>
									{/if}
									{if \App\Core\ModuleHierarchy::accessModulesByLevel(2, 'DetailView')}
										<button class="selectRecord" data-type="0" title="{"LBL_SELECT_RECORD"|t:$MODULE_NAME}">
											<span class="glyphicon glyphicon-resize-small" aria-hidden="true"></span>
										</button>
									{/if}
								</div>
							</div>
						</div>
					{/if}
				</div>
				<div class="data row">
					{if !empty($MODULES_LEVEL_2)}
						<div  data-type="link">
							<div class="col">
								{foreach key=MODULE item=ITEM from=$MODULES_LEVEL_2}
									{foreach item=RELATED from=$RELATED_RECORDS[$MODULE]}
										{include file='MailActionBarRow.tpl'|@vtemplate_path:$MODULE_NAME}
									{/foreach}
								{/foreach}
							</div>
						</div>
					{/if}
				</div>
			</div>	
		</div>
		{if $RELATED_RECORDS}
			<div class="chevronBtnCube">
				<button class="hideBtn" data-type="0" title="{"LBL_MINIMIZE_BAR"|t:$MODULE_NAME}">
					<span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
				</button>
			</div>
		{/if}
	{/if}
<!--/layouts/basic/modules/OSSMail/MailActionBar.tpl -->
{/strip}
