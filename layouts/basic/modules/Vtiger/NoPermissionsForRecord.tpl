{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{strip}
<!-- layouts/basic/modules/Vtiger/NoPermissionsForRecord.tpl -->
	<!DOCTYPE html>
	<html>
		<head>
			<title>Yetiforce: {"LBL_PERMISSION_DENIED"|t}</title>
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="SHORTCUT ICON" href="{vimage_path('favicon.ico')}">
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<link rel="stylesheet" href="libraries/bootstrap/css/bootstrap.css" type="text/css" media="screen">
			<script type="text/javascript" src="libraries/jquery/jquery.min.js"></script>
		</head>
		<body style="background: #ddecf0;">
			<div class="container">
				<div style="margin-top: 70px;" class="alert alert-warning shadow">
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h2 class="alert-heading">{"LBL_PERMISSION_DENIED"|t}</h2>
					<p>{vtranslate($MESSAGE)}</p>
					<p class="Buttons">
						<a class="btn btn-info" href="index.php">{"LBL_MAIN_PAGE"|t}</a>
					</p>
				</div>
			</div>
		</body>
	</html>
<!--/layouts/basic/modules/Vtiger/NoPermissionsForRecord.tpl -->
{/strip}
