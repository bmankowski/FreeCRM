{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce.com
********************************************************************************/
-->*}
{strip}
	<div class="typeRemoveModal" tabindex="-1">
		<div  class="modal fade">
			<div class="modal-dialog modal-lg ">
				<div class="modal-content">
					<div class="modal-header row no-margin">
						<div class="col-xs-12 paddingLRZero">
							<div class="col-xs-8 paddingLRZero">
								<h4>{'LBL_TITLE_TYPE_DELETE'|t:$MODULE}</h4>
							</div>
							<div class="pull-right">
								<button class="btn btn-warning marginLeft10" type="button" data-dismiss="modal" aria-label="Close" aria-hidden="true">&times;</button>
							</div>
						</div>
					</div>
					<div class="modal-body row">
						<div class="col-xs-12">
							<div class="col-xs-12 paddingLRZero marginBottom10px">
								<div class="col-xs-4">
									<button class="btn btn-primary btn-sm typeSavingBtn" data-value="2">
										{'LBL_DELETE_THIS_EVENT'|t:$MODULE}
									</button>
								</div>
								<div class="col-xs-8">
									{'LBL_DELETE_THIS_EVENT_DESCRIPTION'|t:$MODULE}
								</div>
							</div>
							<div class="col-xs-12 paddingLRZero marginBottom10px">	
								<div class="col-xs-4">
									<button class="btn btn-primary btn-sm typeSavingBtn" data-value="3">
										{'LBL_DELETE_FUTURE_EVENTS'|t:$MODULE}
									</button>
								</div>
								<div class="col-xs-8">
									{'LBL_DELETE_FUTURE_EVENTS_DESCRIPTION'|t:$MODULE}
								</div>
							</div>
							<div class="col-xs-12 paddingLRZero marginBottom10px">	
								<div class="col-xs-4">
									<button class="btn btn-primary btn-sm typeSavingBtn" data-value="1">
										{'LBL_DELETE_ALL_EVENTS'|t:$MODULE}
									</button>
								</div>
								<div class="col-xs-8">
									{'LBL_DELETE_ALL_EVENTS_DESCRIPTION'|t:$MODULE}
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	{$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'))}
{/strip}
