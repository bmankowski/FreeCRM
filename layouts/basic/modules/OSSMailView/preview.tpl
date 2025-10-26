{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/OSSMailView/preview.tpl -->
	{if !$NOLOADLIBS}
		{include file="modules/Base/Header.tpl"}
	{/if}
	{if $ISMODAL}
		<div class="modelContainer modal fade" tabindex="-1">
			<div class="modal-dialog modal-blg">
				<div class="modal-content">
				{/if}
				<div class="SendEmailFormStep2 container-fluid" id="emailPreview" name="emailPreview">
					<div class="">
						<div class="blockHeader emailPreviewHeader">
							<h3 class='col-md-4 pushDown'>{"emailPreviewHeader"|t:$MODULENAME}</h3>
							<div class='pull-right'>
								<div class="btn-toolbar" >
									{if AppConfig::main('isActiveSendingMails') && \App\Modules\Users\Models\Privileges::isPermitted('OSSMail')}
										{if $USER_MODEL->get('internal_mailer') == 1}
											{assign var=CONFIG value=OSSMail_Module_Model::getComposeParameters()}	
											{assign var=COMPOSE_URL value=OSSMail_Module_Model::getComposeUrl($SMODULENAME, $SRECORD, 'Detail')}
											{assign var=POPUP value=$CONFIG['popup']}
											<button type="button" class="btn btn-sm btn-default sendMailBtn" data-url="{$COMPOSE_URL}&mid={$RECORD_MODEL->getId()}&type=reply" data-popup="{$POPUP}" title="{"LBL_REPLY"|t:"OSSMailView"}">
												<img width="14px" src="{\App\Runtime\Yeti_Layout::getLayoutFile('modules/OSSMailView/previewReply.png')}" alt="{"LBL_REPLY"|t:"OSSMailView"}">
												&nbsp;&nbsp;<strong>{"LBL_REPLY"|t:"OSSMailView"}</strong>
											</button>
											<button type="button" class="btn btn-sm btn-default sendMailBtn" data-url="{$COMPOSE_URL}&mid={$RECORD_MODEL->getId()}&type=replyAll" data-popup="{$POPUP}" title="{"LBL_REPLYALLL"|t:"OSSMailView"}">
												<img width="14px" src="{\App\Runtime\Yeti_Layout::getLayoutFile('modules/OSSMailView/previewReplyAll.png')}" alt="{"LBL_REPLYALLL"|t:"OSSMailView"}">
												&nbsp;&nbsp;<strong>{"LBL_REPLYALLL"|t:"OSSMailView"}</strong>
											</button>
											<button type="button" class="btn btn-sm btn-default sendMailBtn" data-url="{$COMPOSE_URL}&mid={$RECORD_MODEL->getId()}&type=forward" data-popup="{$POPUP}" title="{"LBL_FORWARD"|t:"OSSMailView"}">
												<span class="glyphicon glyphicon-share-alt"></span>
												&nbsp;&nbsp;<strong>{"LBL_FORWARD"|t:"OSSMailView"}</strong>
											</button>
										{else}
											<a class="btn btn-sm btn-default" href="{OSSMail_Module_Model::getExternalUrlForWidget($RECORD_MODEL, 'reply')}" title="{"LBL_CREATEMAIL"|t:"OSSMailView"}">
												<img width="14px" src="{\App\Runtime\Yeti_Layout::getLayoutFile('modules/OSSMailView/previewReply.png')}" alt="{"LBL_REPLY"|t:"OSSMailView"}">
												&nbsp;&nbsp;<strong>{"LBL_REPLY"|t:"OSSMailView"}</strong>
											</a>
											<a class="btn btn-sm btn-default" href="{OSSMail_Module_Model::getExternalUrlForWidget($RECORD_MODEL, 'replyAll')}" title="{"LBL_REPLYALLL"|t:"OSSMailView"}">
												<img width="14px" src="{\App\Runtime\Yeti_Layout::getLayoutFile('modules/OSSMailView/previewReplyAll.png')}" alt="{"LBL_REPLYALLL"|t:"OSSMailView"}">
												&nbsp;&nbsp;<strong>{"LBL_REPLYALLL"|t:"OSSMailView"}</strong>
											</a>
											<a class="btn btn-sm btn-default" href="{OSSMail_Module_Model::getExternalUrlForWidget($RECORD_MODEL, 'forward')}" title="{"LBL_FORWARD"|t:"OSSMailView"}">
												<span class="glyphicon glyphicon-share-alt"></span>
												&nbsp;&nbsp;<strong>{"LBL_FORWARD"|t:"OSSMailView"}</strong>
											</a>
										{/if}
									{/if}
									{if \App\Modules\Users\Models\Privileges::isPermitted($MODULENAME, 'PrintMail')}
										<span class="btn-group">
											<button id="previewPrint" onclick="OSSMailView_preview_Js.printMail();" type="button" name="previewPrint" class="btn btn-sm btn-default" data-mode="previewPrint">
												<span class="glyphicon glyphicon-print" aria-hidden="true"></span>&nbsp;&nbsp;
												<strong>{"LBL_PRINT"|t:$MODULENAME}</strong>
											</button>
										</span>
									{/if}
									{if $ISMODAL}
										<span class="btn-group">
											<button type="button" class="btn btn-sm btn-danger" data-dismiss="modal" aria-label="Close">
												<span aria-hidden="true">&times;</span>
											</button>
										</span>
									{/if}
								</div>
							</div>
						</div>
						<div class="clearfix"></div>
						<hr>
						<form class="form-horizontal emailPreview">
							<div class="row padding-bottom1per">
								<span class="col-md-2">
									<span class="pull-right muted">{"From"|t:$MODULENAME}</span>
								</span>
								<span class="col-md-9">
									<span id="emailPreview_From" class="">{$FROM}</span>
								</span>
							</div>
							<div class="row padding-bottom1per">
								<span class="col-md-2">
									<span class="pull-right muted">{"To"|t:$MODULENAME}</span>
								</span>
								<span class="col-md-9">
									<span id="emailPreview_To" class="">{assign var=TO_EMAILS value=","|implode:$TO}{$TO_EMAILS}</span>
								</span>
							</div>
							{if !empty($CC)}
								<div class="row padding-bottom1per">
									<span class="col-md-2">
										<span class="pull-right muted">{"CC"|t:$MODULENAME}</span>
									</span>
									<span class="col-md-9">
										<span id="emailPreview_Cc" class="">
											{$CC}
										</span>
									</span>
								</div>
							{/if}
							{if !empty($BCC)}
								<div class="row padding-bottom1per">
									<span class="col-md-2">
										<span class="pull-right muted">{"BCC"|t:$MODULENAME}</span>
									</span>
									<span class="col-md-9">
										<span id="emailPreview_Bcc" class="">
											{$BCC}
										</span>
									</span>
								</div>
							{/if}
							<div class="row padding-bottom1per">
								<span class="col-md-2">
									<span class="pull-right muted">{"Subject"|t:$MODULENAME}</span>
								</span>
								<span class="col-md-9">
									<span id="emailPreview_Subject" class="">
										{$SUBJECT}
									</span>
								</span>
							</div>
							{if !empty($ATTACHMENTS)}
								<div class="row padding-bottom1per">
									<span class="col-md-2">
										<span class="pull-right muted">{"Attachments_Exist"|t:$MODULENAME}</span>
									</span>
									<span class="col-md-9">
										<span id="emailPreview_attachment" class="">
											{foreach item=ATTACHMENT from=$ATTACHMENTS}
												<a class="btn btn-xs btn-primary" title="{$ATTACHMENT['name']}" 
												   href="index.php?module=Documents&action=DownloadFile&record={$ATTACHMENT['id']}">
													<span class="glyphicon glyphicon-paperclip"></span>&nbsp;&nbsp;{$ATTACHMENT['file']}</a>&nbsp;&nbsp;
											{/foreach}
										</span>
									</span>
								</div>
							{/if}
							<div class="row padding-bottom1per content">
								<span class="col-md-2">
									<span class="pull-right muted">{"Content"|t:$MODULENAME}</span>
								</span>
								<span class="col-md-10 row">
									<iframe id="emailPreview_Content" class="col-md-12" src="{$URL}" frameborder="0"></iframe>
								</span>
							</div>
							<hr/>

							<div class="textAlignCenter">
								<span class="muted">
									<small><em>{"Sent"|t:$MODULENAME}</em></small>
									<span><small><em>&nbsp;{$SENT}</em></small></span>
								</span>
							</div>
							<div class="textAlignCenter">
								<span><strong> {"LBL_OWNER"|t} : {\App\Fields\Owner::getLabel($OWNER)}</strong></span>
							</div>
						</form>
					</div>
				</div>
				{if $ISMODAL}
				</div>
			</div>
		</div>
	{/if}
	{if !$NOLOADLIBS}
		{include file='JSResources.tpl'|vtemplate_path}
	{/if}
<!--/layouts/basic/modules/OSSMailView/preview.tpl -->
{/strip}
{if !$ISMODAL}
	<script>
		$('#emailPreview_Content').css('height', document.documentElement.clientHeight - 267);
	</script>
{else}
	{foreach key=index item=jsModel from=$SCRIPTS}
		<script type="{$jsModel->getType()}" src="{$jsModel->getSrc()}"></script>
	{/foreach}
{/if}
