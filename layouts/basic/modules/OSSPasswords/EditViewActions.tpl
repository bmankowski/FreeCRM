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

{strip}
<!-- layouts/basic/modules/OSSPasswords/EditViewActions.tpl -->
		<div class="contentHeader">
			<div class="pull-right">
				<button class="btn btn-success generatePass" name="save" type="button">
					<strong>{$GENERATEPASS|t:$MODULE}</strong>
				</button>&nbsp;
				<button class="btn btn-success" type="submit"><strong>{"LBL_SAVE"|t:$MODULE}</strong></button>&nbsp;
				<button class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();">{"LBL_CANCEL"|t:$MODULE}</button>
			</div>
			<div class="clearfix"></div>
		</div>
	</form>
</div>
<!--/layouts/basic/modules/OSSPasswords/EditViewActions.tpl -->
{/strip}
