{if !empty($IMPORT_STEPS)}
	<nav class="import-steps mb-4" aria-label="{\App\Language::translate('LBL_IMPORT_PROGRESS', $MODULE_NAME)}">
		<ol class="import-steps__list list-unstyled d-flex flex-wrap mb-0">
			{foreach from=$IMPORT_STEPS item=STEP}
				{assign var='STEP_CLASSES' value=[]}
				{if $STEP.active}{append var='STEP_CLASSES' value='is-active'}{/if}
				{if $STEP.completed}{append var='STEP_CLASSES' value='is-complete'}{/if}
				{if !$STEP.enabled}{append var='STEP_CLASSES' value='is-disabled'}{/if}
				<li class="import-steps__item {implode(' ', $STEP_CLASSES)}">
					{if $STEP.enabled && $STEP.url}
						<a class="import-steps__link" href="{$STEP.url|escape:'html'}">
							<span class="import-steps__label">{$STEP.label}</span>
						</a>
					{else}
						<span class="import-steps__link">
							<span class="import-steps__label">{$STEP.label}</span>
						</span>
					{/if}
				</li>
			{/foreach}
		</ol>
	</nav>
{/if}

