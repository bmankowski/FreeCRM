{*<!-- {[The file is published on the basis of YetiForce Public License 6.5 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-RecruitmentCV -->
	<div>
		<code>
			{*            {assign var=PDF value=$RECORD->getCVPathname()}*}
			{assign var=PDF value=$RECORD->getCVPathname()}
			{if !empty($PDF)}
				{*            <object data="{$PDF}#scrollbar=0&navpanes=0&toollbar=0" type="application/pdf" style="min-height:50vh;width:100%">
				<p>Unable to display PDF file. <a href="{$PDF}">Download</a> instead.</p>
				</object>*}
				<center>
					<div style="">
						<img src="{$PDF}" width="1024px" height="100%"/>
					</div>
				</center>
				{*            <object data="{$PDF}#scrollbar=0&navpanes=0&toollbar=0" type="application/pdf" style="min-height:60vh;width:100%">
				<p>Unable to display PDF file. <a href="{$PDF}">Download</a> instead.</p>
				</object>*}
				{*            <object data="pdf/cv.html" style="min-height:50vh;width:100%">
				<p>Unable to display PDF file. <a href="{$PDF}">Download</a> instead.</p>
				</object>    *}

				{*            <embed  src="{$PDF}#scrollbar=0&navpanes=0&toollbar=0" type="application/pdf" width="100%" height="1000px"/>*}
			{/if}
		</code>
	</div>
	<!-- /tpl-RecruitmentCV -->
{/strip}

