{strip}
<!-- layouts/basic/modules/ProjektyRekrutacyjne/partials/KanbanCvSkillsQueryField.tpl -->
{if !isset($FIELD_VALUE)}{assign var=FIELD_VALUE value=''}{/if}
{if !isset($ROWS)}{assign var=ROWS value=4}{/if}
<div class="form-group kanban-cv-skills-query">
	<label for="{$FIELD_ID}">{"LBL_KANBAN_CV_SKILLS"|t:$MODULE_NAME}</label>
	<textarea id="{$FIELD_ID}"
			  class="form-control js-kanban-cv-skills-input"
			  name="cv_skills"
			  rows="{$ROWS}"
			  placeholder="{"LBL_KANBAN_CV_SKILLS_PLACEHOLDER"|t:$MODULE_NAME}">{$FIELD_VALUE|escape:'html'}</textarea>
	<div class="alert alert-danger hidden js-kanban-cv-skills-error" role="alert"></div>
</div>
<!-- /layouts/basic/modules/ProjektyRekrutacyjne/partials/KanbanCvSkillsQueryField.tpl -->
{/strip}
