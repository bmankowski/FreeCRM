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
<!-- layouts/basic/modules/Calendar/EditViewActions.tpl -->
       <div>
            <div class="pull-right">
				<button class="btn btn-primary saveAndComplete" type="button">{"LBL_SAVE_AND_CLOSE"|t:$MODULE}</button> 
				<button class="btn btn-success" type="submit"><strong>{"LBL_SAVE"|t:$MODULE}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-warning" type="reset" onclick="javascript:window.history.back();"><strong>{"LBL_CANCEL"|t:$MODULE}</strong></button>
			</div>
			<div class="clearfix"></div>
        </div>
		<br>
    </form>
</div>
<!--/layouts/basic/modules/Calendar/EditViewActions.tpl -->
{/strip}
