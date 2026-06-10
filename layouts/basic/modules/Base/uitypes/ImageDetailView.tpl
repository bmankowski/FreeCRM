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
<!-- layouts/basic/modules/Base/uitypes/ImageDetailView.tpl -->
{if $MODULE_NAME eq 'Users'}
	<img src="{$RECORD->getImageWebUrl()}" class="user-detail-photo user-detail-photo--compact" alt="">
{else}
	{foreach key=ITER item=IMAGE_INFO from=$RECORD->getImageDetails()}
		{assign var=IMAGE_SRC value=''}
		{if !empty($IMAGE_INFO.url)}
			{assign var=IMAGE_SRC value=$IMAGE_INFO.url}
		{elseif !empty($IMAGE_INFO.path) && !empty($IMAGE_INFO.orgname)}
			{assign var=IMAGE_SRC value="{$IMAGE_INFO.path}_{$IMAGE_INFO.orgname}"}
		{/if}
		{if $IMAGE_SRC neq ''}
			<div class="multiImageContenDiv pull-left" title="{$IMAGE_INFO.orgname|escape:'html'}">
				<div class="contentImage">
					<button type="button" class="btn btn-sm btn-default imageFullModal hide">
						<span class="glyphicon glyphicon-fullscreen"></span>
					</button>
					<img src="{$IMAGE_SRC}" class="multiImageListIcon cursorPointer" alt="">
				</div>
			</div>
		{/if}
	{/foreach}
{/if}
<!--/layouts/basic/modules/Base/uitypes/ImageDetailView.tpl -->
{/strip}
