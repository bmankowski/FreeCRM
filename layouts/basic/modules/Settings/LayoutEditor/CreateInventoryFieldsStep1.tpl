{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Settings/LayoutEditor/CreateInventoryFieldsStep1.tpl -->
	<div class="modal fade" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title">{"LBL_CREATING_INVENTORY_FIELD"|t:$QUALIFIED_MODULE}</h4>
				</div>
				<div class="modal-body">
					<input type="hidden" id="mode" value="step1" />
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-md-5 control-label">{"LBL_SELECT_TYPE_OF_INVENTORY"|t:$QUALIFIED_MODULE}:</label>
							<div class="col-md-7">
								<select name="type" class="select2 form-control type">
									{foreach from=$MODULE_MODELS item=ITEM key=KEY}
										{if ((in_array($ITEM->getColumnName(),$FIELDSEXISTS) && !$ITEM->isOnlyOne()) || !in_array($ITEM->getColumnName(),$FIELDSEXISTS) ) && in_array($BLOCK,$ITEM->getBlocks())}
											<option value="{$ITEM->getName()}">{vtranslate($ITEM->getDefaultLabel(), $QUALIFIED_MODULE)}</option>
										{/if}
									{/foreach}
								</select>
							</div>
						</div>
					</div>
					<div class="well well-small">
						{foreach from=$MODULE_MODELS item=ITEM key=KEY}
							{if ((in_array($ITEM->getColumnName(),$FIELDSEXISTS) && !$ITEM->isOnlyOne()) || !in_array($ITEM->getColumnName(),$FIELDSEXISTS) ) && in_array($BLOCK,$ITEM->getBlocks())}
								<h5>{vtranslate($ITEM->getDefaultLabel(), $QUALIFIED_MODULE)}</h5>
								<p>{vtranslate($ITEM->getDefaultLabel()|cat:'_DESC', $QUALIFIED_MODULE)}</p>
								<hr />
							{/if}
						{/foreach}
					</div>
				</div>
				<div class="modal-footer">
					<div class="pull-right cancelLinkContainer">
						<button class="btn btn-success nextButton" type="submit"><strong>{"LBL_NEXT"|t:$QUALIFIED_MODULE}</strong></button>
						<button class="btn cancelLink btn-warning" type="reset" data-dismiss="modal">{"LBL_CANCEL"|t:$QUALIFIED_MODULE}</button>
					</div>
				</div>
			</div>
		</div>
	</div>
<!--/layouts/basic/modules/Settings/LayoutEditor/CreateInventoryFieldsStep1.tpl -->
{/strip}
