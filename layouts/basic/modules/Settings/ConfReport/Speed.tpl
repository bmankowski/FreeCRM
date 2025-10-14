{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/ConfReport/Speed.tpl -->
		<div class="addIssuesModal validationEngineContainer" tabindex="-1">
			<div  class="modal fade authModalContent">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header row no-margin">
							<div class="col-xs-12 paddingLRZero">
								<div class="col-xs-8 paddingLRZero">
									<h4>{"LBL_SERVER_SPEED_TEST"|t:$QUALIFIED_MODULE}</h4>
								</div>
							</div>
						</div>
						<div class="modal-body row">
							<div class="col-xs-12">
								<h4>{"LBL_HDD"|t:$QUALIFIED_MODULE}:</h4>
								<h5>{"LBL_READ_TEST"|t:$QUALIFIED_MODULE}: {$TESTS['FilesRead']}</h5>
								<h5>{"LBL_WRITE_TEST"|t:$QUALIFIED_MODULE}: {$TESTS['FilesWrite']}</h5>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
<!--/layouts/basic/modules/Settings/ConfReport/Speed.tpl -->
{/strip}
