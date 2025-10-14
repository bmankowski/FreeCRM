{strip}
<!-- Newsletter.tpl -->
	<h3 class="marginTB3">
		{'LBL_SAVE_TO_NEWSLETTER'|t:'Settings:SystemWarnings'}
	</h3>
	<p>{'LBL_NEWSLETTER_DESC'|t:'Settings:SystemWarnings'}</p>
	<form class="form-horizontal row validateForm" method="post" action="index.php">
		<div class="form-group">
			<label class="col-sm-3 control-label"><span class="redColor">*</span>{'First Name'|t}</label>
			<div class="col-sm-9">
				<input type="text" name="first_name" class="form-control" placeholder="{'First Name'|t}" data-validation-engine="validate[required]">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">{'Last Name'|t}</label>
			<div class="col-sm-9">
				<input type="text" name="last_name" class="form-control" placeholder="{'Last Name'|t}">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label"><span class="redColor">*</span>{'LBL_EMAIL_ADRESS'|t}</label>
			<div class="col-sm-9">
				<input type="text" name="email" class="form-control" placeholder="{'LBL_EMAIL_ADRESS'|t}" data-validation-engine="validate[required,custom[email]]">
			</div>
		</div>
		<div class="pull-right">
			<button type="button" class="btn btn-success ajaxBtn">
				<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
				&nbsp;&nbsp;{'LBL_SAVE'|t:'Settings:SystemWarnings'}
			</button>&nbsp;&nbsp;
			<button type="button" class="btn btn-danger cancel">
				<span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
				&nbsp;&nbsp;{'LBL_REMIND_LATER'|t:'Settings:SystemWarnings'}
			</button>
		</div>
	</form>
<!--/Newsletter.tpl -->
{/strip}
