{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 ********************************************************************************/
-->*}
{strip}
<!-- layouts/basic/modules/Calendar/ImportFinalResult.tpl -->
<div>
	<input type="hidden" name="module" value="{$MODULE}" />
	<table class="col-xs-12 paddingLRZero no-margin searchUIBasic well">
		<tr>
			<td class="font-x-large" align="left" colspan="2">
				<strong>{'LBL_IMPORT'|t:$MODULE} {$FOR_MODULE|t:$MODULE} - {'LBL_RESULT'|t:$MODULE}</strong>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<table cellpadding="5" cellspacing="0" align="center" width="100%" class="dvtSelectedCell thickBorder importContents">
					<tr>
						<td>{'LBL_LAST_IMPORT_UNDONE'|t:$MODULE}</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td align="right" colspan="2">
				<a href="index.php?module={$MODULE}&view=List" button name="next" class="create btn btn-success">
					<strong>{'LBL_FINISH'|t:$MODULE}</strong>
				</a>
			</td>
		</tr>
	</table>
<!--/layouts/basic/modules/Calendar/ImportFinalResult.tpl -->
{/strip}
