{if !empty($IMPORT_STEPS)}
	<div class="import-wizard-stepper mb-4">
		<div class="stepper-container">
			{assign var=TOTAL_STEPS value=$IMPORT_STEPS|@count}
			{assign var=STEP_NUM value=1}
			{foreach from=$IMPORT_STEPS item=STEP}
				{assign var='STEP_CLASSES' value=['stepper-item']}
				{if $STEP.active}{append var='STEP_CLASSES' value='active'}{/if}
				{if $STEP.completed}{append var='STEP_CLASSES' value='completed'}{/if}
				{if !$STEP.enabled}{append var='STEP_CLASSES' value='disabled'}{/if}
				
				<div class="{implode(' ', $STEP_CLASSES)}" data-step="{$STEP_NUM}">
					{if $STEP.enabled && $STEP.url && !$STEP.active}
						<a href="{$STEP.url|escape:'html'}" class="stepper-link">
					{/if}
					
					<div class="stepper-icon">
						{if $STEP.completed}
							<i class="fa fa-check"></i>
						{else}
							<span class="stepper-number">{$STEP_NUM}</span>
						{/if}
					</div>
					<div class="stepper-content">
						<span class="stepper-label">{$STEP.label}</span>
						{if $STEP.active}
							<span class="stepper-status">{\App\Language::translate('LBL_CURRENT_STEP', $MODULE_NAME)|default:'Aktualny krok'}</span>
						{elseif $STEP.completed}
							<span class="stepper-status completed">{\App\Language::translate('LBL_COMPLETED', $MODULE_NAME)|default:'Zakończony'}</span>
						{/if}
					</div>
					
					{if $STEP.enabled && $STEP.url && !$STEP.active}
						</a>
					{/if}
					
					{if $STEP_NUM < $TOTAL_STEPS}
						<div class="stepper-connector">
							<div class="stepper-line {if $STEP.completed}completed{/if}"></div>
						</div>
					{/if}
				</div>
				{assign var=STEP_NUM value=$STEP_NUM+1}
			{/foreach}
		</div>
	</div>
{/if}

