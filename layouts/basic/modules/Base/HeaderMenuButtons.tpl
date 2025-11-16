{strip}
	{*<!-- {[The file is published on the basis of FreeCRM Public License that can be found in the following directory: licenses/FreeCRMLicense.html]} --!>*}
<!-- layouts/basic/modules/Base/HeaderMenuButtons.tpl -->
	<div class="pull-right rightHeaderBtnMenu">
		<div class="quickAction">
			<a class="btn btn-default btn-sm" href="#">
				<span aria-hidden="true" class="glyphicon glyphicon-menu-hamburger"></span>
			</a>
		</div>
	</div>
	<div class="pull-right actionMenuBtn">
		<div class="quickAction">
			<a class="btn btn-default btn-sm" href="#">
				<span aria-hidden="true" class="glyphicon glyphicon-tasks"></span>
			</a>
		</div>
	</div>
	{if \App\Core\AppConfig::performance('GLOBAL_SEARCH')}
		<div class="pull-left searchMenuBtn">
			<div class="quickAction">
				<a class="btn btn-default btn-sm" href="#">
					<span aria-hidden="true" class="glyphicon glyphicon-search"></span>
				</a>
			</div>
		</div>
	{/if}
<!--/layouts/basic/modules/Base/HeaderMenuButtons.tpl -->
{/strip}

