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
<div class="widget_header row">
	<div class="col-md-12">
		{include file='BreadCrumbs.tpl'|@vtemplate_path:$MODULE}
	</div>
</div>
{if $ISADMIN eq 1}

	{if $ERROR|count_characters:true gt 0}
		<div class="alert alert-warning">
			<strong>{"Error"|t:$MODULENAME}</strong> {vtranslate($ERROR, $MODULENAME)}
		</div>
	{elseif $INFO|count_characters:true gt 0}
		<div class="alert alert-info">
			<strong>{"Info"|t:$MODULENAME}</strong> {vtranslate($INFO, $MODULENAME)}
		</div>
	{elseif $SUCCESS|count_characters:true gt 0}
		<div class="alert alert-success">
			<strong>{"Success"|t:$MODULENAME}</strong> {vtranslate($SUCCESS, $MODULENAME)}
		</div>
	{/if}

	<ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
		<li class="active"><a href="#encoding" data-toggle="tab">{"Encoding"|t:$MODULENAME}</a></li>
		<li><a href="#confpass" data-toggle="tab">{"LBL_ConfigurePass"|t:$MODULENAME}</a></li>
	</ul>
	<br>
	<div id="my-tab-content" class="tab-content">
		{* encryption configuration *}
		<div class="editViewContainer tab-pane active" id="encoding">
			{* check if the ini file exists *}
			{if $CONFIG neq false}
				<ul id="pills" class="nav nav-pills">
					<li class="active">
						<a href="#edit" data-toggle="tab">{"Edit Password Key"|t:$MODULENAME}</a>
					</li>
					<li><a href="#stop" data-toggle="tab">{"Stop Password Encryption"|t:$MODULENAME}</a></li>
				</ul>
				<div id="my-tab-content2" class="tab-content">
					<div class="editViewContainer tab-pane active" id="edit">
						<form class="form-horizontal recordEditView" id="EditView" name="edit_pass_key" method="post" action="index.php?module={$MODULENAME}&view=ConfigurePass&parent=Settings&parent=Settings">                
							<input type="hidden" name="encrypt" value="edit" />
							<div class="contentHeader row">
								<span class="col-md-8 font-x-x-large textOverflowEllipsis">{"Change Password Key"|t:$MODULENAME}</span>
							</div>

							<div class="panel panel-default row marginLeftZero marginRightZero blockContainer">
								<div class="row blockHeader panel-heading marginLeftZero marginRightZero">
									<h5>&nbsp;{"Edit Encryption Key"|t:$MODULENAME}</h5>
								</div>
								<div class="col-md-12 paddingLRZero panel-body blockContent">									
									<div class="fieldRow col-md-8 col-xs-12">
										<div class="fieldLabel col-xs-5 col-sm-2">
											<label class="muted pull-right marginRight10px"> <span class="redColor">*</span> {"Old Key"|t:$MODULENAME}:</label>
										</div>
										<div class="fieldValue col-xs-7 col-sm-10" >
											<div class="row">
												<input id="oldKey" type="text" class="form-control nameField" name="oldKey" value="" min="8" />
											</div>
										</div>
									</div>
									<div class="fieldRow col-md-8 col-xs-12">
										<div class="fieldLabel col-xs-5 col-sm-2">
											<label class="muted pull-right marginRight10px"> <span class="redColor">*</span> {"New Key"|t:$MODULENAME}:</label>
										</div>
										<div class="fieldValue col-xs-7 col-sm-10" >
											<div class="row">
												<input id="newKey" type="text" class="form-control nameField" name="newKey" value="" min="8" />
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="contentHeader">
								<span class="pull-right">
									<button class="btn btn-success" name="encryption_pass" value="encryption_pass" type="submit"><strong>{"Save"|t:$MODULENAME}</strong></button>
									<a class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();">{"Cancel"|t:$MODULENAME}</a>
								</span>
							</div>
						</form>
					</div>
					{* stop encrypting passwords *}
					<div class="editViewContainer tab-pane" id="stop">
						<form class="form-horizontal recordEditView" id="EditView" name="EditView" method="post" action="index.php?module={$MODULENAME}&view=ConfigurePass&parent=Settings">                
							<input type="hidden" name="encrypt" value="stop" />
							<div class="contentHeader row">
								<span class="col-md-8 font-x-x-large textOverflowEllipsis">{"Cancel Encrypting Passwords"|t:$MODULENAME}</span>
							</div>
							<div class="panel panel-default row marginLeftZero marginRightZero blockContainer">
								<div class="row blockHeader panel-heading marginLeftZero marginRightZero">
									<h5>&nbsp;{"Enter Your Old Password"|t:$MODULENAME}</h5>
								</div>
								<div class="col-md-12 paddingLRZero panel-body blockContent">									
									<div class="fieldRow col-md-8 col-xs-12">
										<div class="fieldLabel col-xs-5 col-sm-2">
											<label class="muted pull-right marginRight10px"> <span class="redColor">*</span> {"Encryption Password"|t:$MODULENAME}:</label>
										</div>
										<div class="fieldValue col-xs-7 col-sm-10" >
											<div class="row">
												<input id="passKey" type="text" class="form-control nameField" name="passKey" value="" min="8" />
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="contentHeader">
								<span class="pull-right">
									<button class="btn btn-success" name="encryption_pass" value="encryption_pass" type="submit"><strong>{"Save"|t:$MODULENAME}</strong></button>
									<a class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();">{"Cancel"|t:$MODULENAME}</a>
								</span>
							</div>
						</form>
					</div>
				</div>
			{else}
				<form class="form-horizontal recordEditView" id="EditView" method="post" action="index.php?module={$MODULENAME}&view=ConfigurePass&parent=Settings">
					<input type="hidden" name="encrypt" value="start" />
					<div class="contentHeader row">
						<span class="col-md-8 font-x-x-large textOverflowEllipsis">{"Encrypt Passwords"|t:$MODULENAME}</span>
					</div>

					<div class="panel panel-default row marginLeftZero marginRightZero blockContainer">
						<div class="row blockHeader panel-heading marginLeftZero marginRightZero">
							<h5>&nbsp;{"Enter encryption password"|t:$MODULENAME}</h5>
						</div>
						<div class="col-md-12 paddingLRZero panel-body blockContent">
							<div class="fieldRow col-md-8 col-xs-12">
								<div class="fieldLabel col-xs-5 col-sm-2">
									<label class="muted pull-right marginRight10px"> <span class="redColor">*</span> {"Encryption password"|t:$MODULENAME}:</label>
								</div>
								<div class="fieldValue col-xs-7 col-sm-10">
									<div class="row">
										<input id="pass_key" type="text" class="form-control nameField" name="pass_key" value="" min="8" />
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="contentHeader">
						<span class="pull-right">
							<button class="btn btn-success" name="encryption_pass" value="encryption_pass" type="submit"><strong>{"Save"|t:$MODULENAME}</strong></button>
							<a class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();">{"Cancel"|t:$MODULENAME}</a>
						</span>
					</div>
				</form>
			{/if}
		</div>

		{* password configuration form *}
		<div class="editViewContainer tab-pane" id="confpass">
			<form class="form-horizontal recordEditView" id="EditView" name="EditView" method="post" action="index.php?module={$MODULENAME}&view=ConfigurePass&parent=Settings">
				<div class="contentHeader row">
					<span class="col-md-8 font-x-x-large textOverflowEllipsis">{"LBL_ConfigurePass"|t:$MODULENAME}</span>
				</div>
				
				<div class="panel panel-default row marginLeftZero marginRightZero blockContainer">
					<div class="row blockHeader panel-heading marginLeftZero marginRightZero">
						<h5>&nbsp;{"Password Length"|t:$MODULENAME}</h5>
					</div>
					<div class="col-md-12 paddingLRZero panel-body blockContent">
						<div class="fieldRow col-md-8 col-xs-12">
							<div class="fieldLabel col-xs-5 col-sm-2">
								<label class="muted pull-right marginRight10px"> <span class="redColor">*</span> {"Minimum Length"|t:$MODULENAME}:</label>
							</div>
							<div class="fieldValue col-xs-7 col-sm-10">
								<div class="row">
									<input id="OSSPasswords_editView_fieldName_pass_length_min" type="number" class="form-control nameField" name="pass_length_min" value="{$MIN}" min="1" />
								</div>
							</div>
						</div>
						<div class="fieldRow col-md-8 col-xs-12">
							<div class="fieldLabel col-xs-5 col-sm-2">
								<label class="muted pull-right marginRight10px"> <span class="redColor">*</span> {"Maximum Length"|t:$MODULENAME}:</label>
							</div>
							<div class="fieldValue col-xs-7 col-sm-10">
								<div class="row">
									<input id="OSSPasswords_editView_fieldName_pass_length_max" type="number" class="form-control nameField" name="pass_length_max" value="{$MAX}" min="1" />
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-default row marginLeftZero marginRightZero blockContainer">
					<div class="row blockHeader panel-heading marginLeftZero marginRightZero">
						<h5>&nbsp;{"Allowed Characters"|t:$MODULENAME}</h5>
					</div>
					<div class="col-md-12 paddingLRZero panel-body blockContent">
						<div class="fieldRow col-md-8 col-xs-12">
							<div class="fieldLabel"> </div>
							<div align="center" class="fieldValue col-xs-12">
								<div class="row">
									<textarea id="OSSPasswords_editView_fieldName_pass_allow_chars" class="form-control" name="pass_allow_chars" rows="4" cols="80">{$ALLOWEDCHARS}</textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="panel panel-default row marginLeftZero marginRightZero blockContainer">
					<div class="row blockHeader panel-heading marginLeftZero marginRightZero">
						<h5>&nbsp;{"LBL_REGISTER_CHANGES"|t:$MODULENAME}</h5>
					</div>
					<div class="col-md-12 paddingLRZero panel-body blockContent">
						<div class="fieldRow col-md-8 col-xs-12">
							<div class="fieldLabel"> </div>
							<div align="center" class="fieldValue col-xs-7 col-sm-10">
								<div class="row pull-left">
									<input id="register_changes" type="checkbox" class="nameField" name="register_changes" {$REGISTER} value="1" /> 
										{"LBL_START_REGISTER"|t:$MODULENAME}
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="contentHeader">
					<span class="pull-right">
						<button class="btn btn-success" name="save" value="save" type="submit"><strong>{"Save"|t:$MODULENAME}</strong></button>
						<a class="cancelLink btn btn-warning" type="reset" onclick="javascript:window.history.back();">{"Cancel"|t:$MODULENAME}</a>
					</span>
				</div>
			</form>
		</div>
	</div>

	{* modal promtp for modtracker register changes *}
	<div id="myRegisterModal" class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h3 class="modal-title">{"LBL_REGISTER_WARN1"|t:$MODULENAME}</h3>
				</div>
				<div class="modal-body">
					<p>{"LBL_REGISTER_WARN2"|t:$MODULENAME}</p>
					<p><input id="statusRegistration" name="status" type="checkbox" {$REGISTER} value="1" required="required" /> {"LBL_START_REGISTER"|t:$MODULENAME}</p>
				</div>
				<div class="modal-footer">
					<button class="btn btn-success okay-button" id="confirmRegistration" type="submit" name="uninstall" form="EditView">{"Yes"|t:$MODULENAME}</button>
					<button class="btn btn-warning" data-dismiss="modal">{"No"|t:$MODULENAME}</button>
				</div>
			</div>
		</div>
	</div>
{else}
    <div class="alert alert-warning" style="margin:10px 15px;">
        <strong>{"Error"|t:$MODULENAME}</strong> {"Access denied!"|t:$MODULENAME}
    </div>
{/if}
