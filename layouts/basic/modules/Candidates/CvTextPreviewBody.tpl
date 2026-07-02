{strip}
<!-- layouts/basic/modules/Candidates/CvTextPreviewBody.tpl -->
<div class="cv-text-preview">
	{if !empty($CANDIDATE_NAME)}
		<h4 class="cv-text-preview__title">{$CANDIDATE_NAME|escape}</h4>
	{/if}
	{if !empty($CV_TEXT_HTML)}
		<div class="cv-text-preview__body">{$CV_TEXT_HTML nofilter}</div>
	{else}
		<p class="cv-text-preview__empty">{"LBL_KANBAN_CV_TEXT_EMPTY"|t:"Candidates"}</p>
	{/if}
</div>
<!-- /layouts/basic/modules/Candidates/CvTextPreviewBody.tpl -->
{/strip}
