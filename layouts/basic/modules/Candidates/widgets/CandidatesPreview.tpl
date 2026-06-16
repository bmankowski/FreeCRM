{*<!-- {[The file is published on the basis of YetiForce Public License 6.5 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-RecruitmentCV -->
	{assign var=CV_IMG value=$RECORD->getCVPathname()}
	<div class="summaryWidgetContainer summaryWidgetContainer--cv-preview">
		<div class="widgetContentBlock" data-name="{$WIDGET['label']|escape:'html'}">
			<div class="widget_contents">
				{if !empty($CV_IMG)}
					<div class="candidates-cv-preview">
						<a href="{$CV_IMG}" target="_blank" rel="noopener noreferrer" class="candidates-cv-preview__link">
							<img src="{$CV_IMG}" class="candidates-cv-preview__image" alt="CV" loading="eager" decoding="async" />
						</a>
					</div>
				{/if}
			</div>
		</div>
	</div>
	<!-- /tpl-RecruitmentCV -->
{/strip}
