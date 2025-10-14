{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
* Contributor(s): YetiForce.com
********************************************************************************/
-->*}
<!DOCTYPE html>
<html>
	<head>
		<title>Yetiforce: {"LBL_ERROR"|t}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="SHORTCUT ICON" href="{vimage_path('favicon.ico')}">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<link rel="stylesheet" href="libraries/bootstrap3/css/bootstrap.css" type="text/css" media="screen">
		<script type="text/javascript" src="libraries/jquery/jquery.min.js"></script>
		<script type="text/javascript" src="libraries/jquery/jquery-migrate.js"></script>
		<style>
			.shadow{
				-webkit-box-shadow: 3px 3px 14px 0px rgba(50, 50, 50, 0.75);
				-moz-box-shadow:    3px 3px 14px 0px rgba(50, 50, 50, 0.75);
				box-shadow:         3px 3px 14px 0px rgba(50, 50, 50, 0.75);
			}
		</style>
	</head>
	<body class="container">
		<div style="margin-top: 10px;" class="col-md-12 alert alert-warning shadow">
			<div style="position: relative;" >
				<div>
					<h2 class="alert-heading">{"CANNOT_CONVERT"|t:$MODULE}</h2>
					<p>
					<ul> {"LBL_FOLLOWING_ARE_POSSIBLE_REASONS"|t:$MODULE}
						<li>{"LBL_LEADS_FIELD_MAPPING_INCOMPLETE"|t:$MODULE}</li>
						<li>{"LBL_MANDATORY_FIELDS_ARE_EMPTY"|t:$MODULE}</li>
							{if $EXCEPTION}
							<li><strong>{$EXCEPTION}</strong></li>
								{/if}
					</ul>
					</p>
					<p class="clearfix"></p>
					<p class="Buttons pull-right">
						{if $CURRENT_USER->isAdminUser()}
							<a class="btn btn-info" href='index.php?parent=Settings&module=Leads&view=MappingDetail'>{"LBL_LEADS_FIELD_MAPPING"|t:$MODULE}</a>
						{/if}
						<a class="btn btn-warning" href="javascript:window.history.back();">{"LBL_GO_BACK"|t}</a>
						<a class="btn btn-primary" href="index.php">{"LBL_MAIN_PAGE"|t}</a>
					</p>
				</div>
			</div>
		</div>
	</body>
</html>
