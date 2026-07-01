{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Base/RecordNotFound.tpl -->
	<!DOCTYPE html>
	<html>
		<head>
			<title>FreeCRM: {if $MESSAGE eq 'LBL_RECORD_DELETE'}{"LBL_RECORD_DELETE_TITLE"|t}{else}{"LBL_RECORD_NOT_FOUND_TITLE"|t}{/if}</title>
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="SHORTCUT ICON" href="{vimage_path('favicon.ico')}">
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<link rel="stylesheet" href="libraries/bootstrap/css/bootstrap.css" type="text/css" media="screen">
			<script type="text/javascript" src="libraries/jquery/jquery.min.js"></script>
		</head>
		<body style="background: #f5f7fa;">
			<div class="container">
				<div style="margin-top: 70px;" class="alert alert-info shadow">
					<h2 class="alert-heading">
						{if $MESSAGE eq 'LBL_RECORD_DELETE'}
							{"LBL_RECORD_DELETE_TITLE"|t}
						{else}
							{"LBL_RECORD_NOT_FOUND_TITLE"|t}
						{/if}
					</h2>
					<p>{$MESSAGE|t}</p>
					<p class="Buttons">
						<a class="btn btn-warning" href="javascript:window.history.back();">{"LBL_GO_BACK"|t}</a>
						<a class="btn btn-info" href="index.php">{"LBL_MAIN_PAGE"|t}</a>
					</p>
					{include file='ErrorStackTraceButton.tpl'|@vtemplate_path:'Base'}
				</div>
			</div>
		</body>
	</html>
<!--/layouts/basic/modules/Base/RecordNotFound.tpl -->
{/strip}
