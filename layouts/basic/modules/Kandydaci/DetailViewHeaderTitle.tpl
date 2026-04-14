{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce S.A.
********************************************************************************/
-->*}
{strip}
	<!-- tpl-Kandydaci-DetailViewHeaderTitle -->
	<div class="col-md-12 paddingLRZero row">
		<div class="col-xs-12 col-sm-12 col-md-8">
			<div class="moduleIcon">
				{assign var=COUNT_IN_HIERARCHY value=\App\Core\AppConfig::module($MODULE_NAME, 'COUNT_IN_HIERARCHY')}
				{if $COUNT_IN_HIERARCHY}
					<span class="hierarchy">
						<span class="badge {if $RECORD->get('active')} bgGreen {else} bgOrange {/if}"></span>
					</span>
				{/if}
				<span class="detailViewIcon {if $COUNT_IN_HIERARCHY}cursorPointer{/if} userIcon-{$MODULE}" {if $COLORLISTHANDLERS}style="background-color: {$COLORLISTHANDLERS['background']};color: {$COLORLISTHANDLERS['text']};"{/if}></span>
			</div>
			<div class="paddingLeft5px">
				<h4 class="recordLabel textOverflowEllipsis pushDown marginbottomZero" title="{$RECORD->getName()}">
					<span class="moduleColor_{$MODULE_NAME}">{$RECORD->getName()}</span>
				</h4>
				{include file='DetailViewHeaderFields.tpl'|@vtemplate_path:$MODULE_NAME}
			</div>
		</div>
		
	</div>
	<!-- /tpl-Kandydaci-DetailViewHeaderTitle -->
{/strip}

