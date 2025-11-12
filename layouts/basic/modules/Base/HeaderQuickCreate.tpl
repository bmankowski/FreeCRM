{strip}
	{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/FreeCRMLicense.html]} --!>*}
<!-- layouts/basic/modules/Base/HeaderQuickCreate.tpl -->
	{assign var='count' value=0}
	{assign var=QUICKCREATE_MODULES value=\App\Modules\Base\Models\Module::getQuickCreateModules(true)}
	{if !empty($QUICKCREATE_MODULES)}
		<a class="btn btn-default btn-sm popoverTooltip dropdownMenu" data-content="{'LBL_QUICK_CREATE'|t}" href="#">
			<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
		</a>
		<ul class="dropdown-menu dropdown-menu-right commonActionsButtonDropDown">
			<li class="quickCreateModules">
				<div class="panel-default">
					<div class="panel-heading">
						<h4 class="panel-title"><strong>{'LBL_QUICK_CREATE'|t}</strong></h4>
					</div>
					<div class="panel-body paddingLRZero">
						{foreach key=NAME item=MODULEMODEL from=$QUICKCREATE_MODULES}
							{assign var='quickCreateModule' value=$MODULEMODEL->isQuickCreateSupported()}
							{assign var='singularLabel' value=$MODULEMODEL->getSingularLabelKey()}
							{if $singularLabel == 'SINGLE_Calendar'}
								{assign var='singularLabel' value='LBL_EVENT_OR_TASK'}
							{/if}	
							{if $quickCreateModule == '1'}
								{if $count % 3 == 0}
									<div class="">
								{/if}
									<div class="col-xs-4{if $count % 3 != 2} paddingRightZero{/if}">
										<a id="menubar_quickCreate_{$NAME}" class="quickCreateModule list-group-item" data-name="{$NAME}" data-url="{$MODULEMODEL->getQuickCreateUrl()}" href="javascript:void(0)" title="{$singularLabel|t:$NAME}">
											<span>{$singularLabel|t:$NAME}</span>
										</a>
									</div>
								{if $count % 3 == 2}
									</div>
								{/if}
								{assign var='count' value=$count+1}
							{/if}
						{/foreach}
						{if $count % 3 >= 1}
						</div>
					{/if}
					</div>
				</div>
			</li>
		</ul>
	{/if}
<!--/layouts/basic/modules/Base/HeaderQuickCreate.tpl -->
{/strip}

