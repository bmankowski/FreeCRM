{strip}
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CV</title>
	<style>
		{literal}
		html {
			box-sizing: border-box;
			height: 100%;
		}
		*, *::before, *::after {
			box-sizing: inherit;
		}
		body {
			margin: 0;
			height: 100%;
			min-height: 100%;
			display: flex;
			flex-direction: column;
			overflow: hidden;
			background: #fff;
		}
		.cv-text-preview-scroll {
			flex: 1 1 auto;
			min-height: 0;
			width: 100%;
			overflow-y: auto;
			overflow-x: hidden;
			-webkit-overflow-scrolling: touch;
			padding: 10px 12px;
		}
		.cv-text-preview {
			font-size: 13px;
			line-height: 1.5;
			color: #2b2b2b;
		}
		.cv-text-preview__title {
			margin: 0 0 12px;
			font-size: 16px;
			font-weight: 600;
		}
		.cv-text-preview__body {
			white-space: normal;
			word-break: break-word;
		}
		.cv-text-preview__mark {
			background: #fff3cd;
			padding: 0 1px;
			border-radius: 2px;
		}
		.cv-text-preview__empty {
			color: #888;
			font-style: italic;
		}
		{/literal}
	</style>
</head>
<body>
	<div class="cv-text-preview-scroll">
		{include file='CvTextPreviewBody.tpl'|@vtemplate_path:'Candidates'}
	</div>
</body>
</html>
<!-- /layouts/basic/modules/Candidates/CvTextPreviewIframe.tpl -->
{/strip}
