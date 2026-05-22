{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
	<!-- layouts/basic/modules/Base/VariablePanelWithRelatedTables.tpl -->
	{assign var=VP_LAYOUT value=$VARIABLE_PANEL_LAYOUT|default:'grid'}
	{if !empty($TEXT_PARSER) && $PARSER_TYPE}
		{assign var=TEXT_PARSER value=$TEXT_PARSER->setType($PARSER_TYPE)}
	{/if}
	{if !empty($TEXT_PARSER)}
		{if $VP_LAYOUT eq 'form'}
			<div class="form-group variablePanelFormRow">
				<label class="control-label col-md-3">{'LBL_ADDITIONAL_VARIABLES'|t}</label>
				<div class="controls col-md-8">
					<div class="input-group variablePanelInputGroup">
						<select class="select2 form-control" id="generalVariable">
							{foreach item=FIELDS key=BLOCK_NAME from=$TEXT_PARSER->getGeneralVariable()}
								<optgroup label="{$BLOCK_NAME|t}">
									{foreach item=LABEL key=VARIABLE from=$FIELDS}
										<option value="{$VARIABLE}">{$LABEL}</option>
									{/foreach}
								</optgroup>
							{/foreach}
						</select>
						<div class="input-group-btn">
							<button type="button" class="btn btn-primary clipboard" data-copy-target="#generalVariable"
								title="{"LBL_COPY_TO_CLIPBOARD"|t}">
								<span class="glyphicon glyphicon-copy"></span>
							</button>
						</div>
					</div>
				</div>
			</div>
		{else}
			<div class="col-md-12 fieldRow">
				<div class="col-md-3 fieldLabel paddingLeft5px medium {if !empty($GRAY)}bc-gray-lighter{/if}">
					<label class="muted">{'LBL_ADDITIONAL_VARIABLES'|t}</label>
				</div>
				<div class="medium col-md-9 fieldValue">
					<div class="input-group">
						<select class="select2 form-control" id="generalVariable">
							{foreach item=FIELDS key=BLOCK_NAME from=$TEXT_PARSER->getGeneralVariable()}
								<optgroup label="{$BLOCK_NAME|t}">
									{foreach item=LABEL key=VARIABLE from=$FIELDS}
										<option value="{$VARIABLE}">{$LABEL}</option>
									{/foreach}
								</optgroup>
							{/foreach}
						</select>
						<div class="input-group-btn">
							<button type="button" class="btn btn-primary clipboard" data-copy-target="#generalVariable"
								title="{"LBL_COPY_TO_CLIPBOARD"|t}">
								<span class="glyphicon glyphicon-copy"></span>
							</button>
						</div>
					</div>
				</div>
			</div>
		{/if}
	{/if}
	{if !empty($RELATION_VARIABLE_PANEL_SECTIONS)}
		{foreach from=$RELATION_VARIABLE_PANEL_SECTIONS item=SECTION}
			{if $VP_LAYOUT eq 'form'}
				<div class="form-group variablePanelFormRow">
					<label class="control-label col-md-3">{$SECTION.section_label}</label>
					<div class="controls col-md-8">
						<div class="input-group variablePanelInputGroup">
							<select class="select2 form-control" id="{$SECTION.select_id}">
								{foreach item=FIELDS key=BLOCK_NAME from=$SECTION.blocks}
									<optgroup label="{$BLOCK_NAME|t:$SECTION.module}">
										{foreach item=ITEM from=$FIELDS}
											<option value="{$ITEM.var_value|escape}">{$ITEM.label|escape}</option>
										{/foreach}
									</optgroup>
								{/foreach}
							</select>
							<div class="input-group-btn">
								<button type="button" class="btn btn-primary clipboard" data-copy-target="#{$SECTION.select_id}"
									title="{"LBL_COPY_TO_CLIPBOARD"|t} - {"LBL_COPY_VALUE"|t}">
									<span class="glyphicon glyphicon-copy"></span>
								</button>
							</div>
						</div>
					</div>
				</div>
			{else}
				<div class="col-md-12 fieldRow">
					<div class="col-md-3 fieldLabel paddingLeft5px medium {if !empty($GRAY)}bc-gray-lighter{/if}">
						<label class="muted">{$SECTION.section_label}</label>
					</div>
					<div class="medium col-md-9 fieldValue">
						<div class="input-group">
							<select class="select2 form-control" id="{$SECTION.select_id}">
								{foreach item=FIELDS key=BLOCK_NAME from=$SECTION.blocks}
									<optgroup label="{$BLOCK_NAME|t:$SECTION.module}">
										{foreach item=ITEM from=$FIELDS}
											<option value="{$ITEM.var_value|escape}">{$ITEM.label|escape}</option>
										{/foreach}
									</optgroup>
								{/foreach}
							</select>
							<div class="input-group-btn">
								<button type="button" class="btn btn-primary clipboard" data-copy-target="#{$SECTION.select_id}"
									title="{"LBL_COPY_TO_CLIPBOARD"|t} - {"LBL_COPY_VALUE"|t}">
									<span class="glyphicon glyphicon-copy"></span>
								</button>
							</div>
						</div>
					</div>
				</div>
			{/if}
		{/foreach}
	{/if}
	<!--/layouts/basic/modules/Base/VariablePanelWithRelatedTables.tpl -->
{/strip}
