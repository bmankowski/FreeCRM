{*<!-- {[The file is published on the basis of YetiForce Public License 6.5 that can be found in the following directory: licenses/LicenseEN.txt or yetiforce.com]} -->*}
{strip}
	<!-- tpl-RecruitmentCV -->
	{assign var=WIDGET_UID value="id-{App\Layout::getUniqueId($WIDGET['id']|cat:_)}"}
	{assign var=RECRUITMENT_PROJECTS value=$WIDGET['data']['recruitmentProjects']}
<div>
	<table class="table table-bordered">
		<thead>
			<tr>
{*				<th>Project ID</th>*}
				<th>Nazwa projektu</th>
				<th>Status</th>
				<th>Komentarz</th>
				<th>Data</th>
{*				<th>Created User</th>*}
			</tr>
		</thead>
		<tbody>
			{foreach from=$RECRUITMENT_PROJECTS item=RECRUITMENT_PROJECT name=recruitmentProjects}
				<tr>
{*					<td>{$RECRUITMENT_PROJECT['id']}</td>*}
					<td><a href = "{$RECRUITMENT_PROJECT['url']}">{$RECRUITMENT_PROJECT['name']}</a></td>
					<td>{App\Language::translate({$RECRUITMENT_PROJECT['status']},$MODULE_NAME)}</td>
					<td>{$RECRUITMENT_PROJECT['comment']}</td>
					<td>{$RECRUITMENT_PROJECT['created_time']}</td>
{*					<td>{$RECRUITMENT_PROJECT['created_user']}</td>*}
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>
{/strip}
