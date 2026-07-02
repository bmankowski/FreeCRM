{strip}
<!-- layouts/basic/modules/HelpDesk/TicketWorkflowModal.tpl -->
<div class="modal-header">
	<h3 class="modal-title">
		{if $MODE eq 'done'}
			{"LBL_TICKET_MARK_DONE"|t:$MODULE_NAME}
		{elseif $MODE eq 'accept'}
			{"LBL_TICKET_ACCEPT"|t:$MODULE_NAME}
		{else}
			{"LBL_TICKET_STILL_NOT_WORKING"|t:$MODULE_NAME}
		{/if}
	</h3>
</div>
<div class="modal-body">
	<form id="ticketWorkflowForm" data-mode="{$MODE}" data-record="{$RECORD->getId()}">
		{if $MODE eq 'done'}
			<div class="form-group">
				<label for="ticketWorkflowSolution">{"Solution"|t:$MODULE_NAME}</label>
				<textarea id="ticketWorkflowSolution" class="form-control" name="solution" rows="6">{$SOLUTION|escape}</textarea>
			</div>
			<div class="form-group">
				<label for="ticketWorkflowComment">{"LBL_TICKET_WORKFLOW_COMMENT"|t:$MODULE_NAME}</label>
				<textarea id="ticketWorkflowComment" class="form-control" name="comment" rows="4"></textarea>
			</div>
		{else}
			<div class="form-group">
				<label for="ticketWorkflowComment">
					{if $MODE eq 'accept'}
						{"LBL_TICKET_WORKFLOW_COMMENT"|t:$MODULE_NAME}
					{else}
						{"LBL_TICKET_WORKFLOW_REMARKS"|t:$MODULE_NAME}
					{/if}
				</label>
				<textarea id="ticketWorkflowComment" class="form-control" name="comment" rows="6"></textarea>
			</div>
		{/if}
	</form>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-success js-ticket-workflow-submit">{"LBL_SAVE"|t:"Vtiger"}</button>
	<button type="button" class="btn btn-warning" data-dismiss="modal">{"LBL_CANCEL"|t:"Vtiger"}</button>
</div>
<!--/layouts/basic/modules/HelpDesk/TicketWorkflowModal.tpl -->
{/strip}
