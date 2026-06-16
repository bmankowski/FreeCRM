<?php
/**
 * Regenerate licenses/Credits.html from the curated dependency list.
 * Run: docker compose exec -T app php scripts/generate-credits.php
 */

$credits = [
	'Lineage' => [
		[
			'name' => 'YetiForce CRM',
			'version' => '',
			'license' => 'YetiForce Public License 1.1',
			'url' => 'https://yetiforce.com/',
			'body' => 'FreeCRM is derived from the YetiForce CRM codebase.',
		],
		[
			'name' => 'Vtiger CRM',
			'version' => '6.4.0',
			'license' => 'VPL 1.1',
			'url' => 'https://www.vtiger.com/',
			'body' => 'YetiForce was forked from Vtiger CRM. The Vtiger Public License 1.1 is based on the Mozilla Public License 1.1.',
		],
	],
	'PHP — Composer' => [
		['name' => 'Yii 2 framework', 'version' => '2.0.53', 'license' => 'BSD-3-Clause', 'url' => 'https://www.yiiframework.com/', 'body' => 'Core application framework (database, logging, migrations).'],
		['name' => 'Smarty', 'version' => '4.5.6', 'license' => 'LGPL-3.0', 'url' => 'https://www.smarty.net/', 'body' => 'Template engine for CRM views.'],
		['name' => 'PHPMailer', 'version' => '7.1.1', 'license' => 'LGPL-2.1', 'url' => 'https://github.com/PHPMailer/PHPMailer', 'body' => 'Outbound email transport.'],
		['name' => 'HTML Purifier', 'version' => '4.19.0', 'license' => 'LGPL-2.1', 'url' => 'https://htmlpurifier.org/', 'body' => 'HTML sanitization.'],
		['name' => 'PhpSpreadsheet', 'version' => '5.7.0', 'license' => 'MIT', 'url' => 'https://github.com/PHPOffice/PhpSpreadsheet', 'body' => 'Excel export (Quick Export, Reports).'],
		['name' => 'webklex/php-imap', 'version' => '5.5.0', 'license' => 'MIT', 'url' => 'https://github.com/Webklex/php-imap', 'body' => 'IMAP mail integration.'],
		['name' => 'Requests for PHP', 'version' => '1.7.0', 'license' => 'ISC', 'url' => 'https://github.com/WordPress/Requests', 'body' => 'HTTP client for integrations (RSS, SMS, PBX).'],
		['name' => 'Recurr', 'version' => '2.2.3', 'license' => 'MIT', 'url' => 'https://github.com/simshaun/recurr', 'body' => 'Recurring calendar event expansion.'],
		['name' => 'ANTLR4 PHP runtime', 'version' => '0.9.1', 'license' => 'BSD-3-Clause', 'url' => 'https://github.com/antlr/antlr4-php-runtime', 'body' => 'Workflow condition expression parser.'],
		['name' => 'libphonenumber-for-php', 'version' => '9.0.28', 'license' => 'Apache-2.0', 'url' => 'https://github.com/giggsey/libphonenumber-for-php', 'body' => 'Phone number parsing and validation.'],
		['name' => 'Linfo', 'version' => '3.0.1', 'license' => 'GPL', 'url' => 'https://github.com/jrgp/linfo', 'body' => 'Server diagnostics in Settings.'],
	],
	'PHP — Bundled' => [
		['name' => 'SabreDAV', 'version' => '3.1.3', 'license' => 'BSD-3-Clause', 'url' => 'https://sabre.io/', 'body' => 'CalDAV and CardDAV API.'],
		['name' => 'HTTP_Session', 'version' => '', 'license' => 'PHP License 3.0', 'url' => 'https://pear.php.net/package/HTTP_Session', 'body' => 'Session management for webservices.'],
		['name' => 'RSS & Atom Feeds for PHP', 'version' => '1.2', 'license' => 'BSD', 'url' => 'https://github.com/dg/rss-php', 'body' => 'RSS dashboard widget.'],
		['name' => 'CSRF-magic', 'version' => '1.0.4', 'license' => 'BSD', 'url' => 'http://csrf.htmlpurifier.org/', 'body' => 'CSRF protection for forms.'],
	],
	'JavaScript & CSS — Core UI' => [
		['name' => 'jQuery', 'version' => '2.1.4', 'license' => 'MIT/GPL', 'url' => 'https://jquery.com/', 'body' => 'DOM and AJAX foundation.'],
		['name' => 'jQuery Migrate', 'version' => '1.2.1', 'license' => 'MIT', 'url' => 'https://github.com/jquery/jquery-migrate', 'body' => 'jQuery 1.x compatibility shims.'],
		['name' => 'jQuery UI', 'version' => '', 'license' => 'MIT', 'url' => 'https://jqueryui.com/', 'body' => 'UI widgets and interactions.'],
		['name' => 'Bootstrap', 'version' => '3.3.5', 'license' => 'MIT', 'url' => 'https://getbootstrap.com/', 'body' => 'Primary UI framework (Bootstrap 3).'],
		['name' => 'Bootstrap utilities', 'version' => '5.3.8', 'license' => 'MIT', 'url' => 'https://getbootstrap.com/', 'body' => 'Bootstrap 5 utility classes (selected modules).'],
		['name' => 'Font Awesome', 'version' => '6.5.2', 'license' => 'SIL OFL 1.1 / MIT / CC BY 4.0', 'url' => 'https://fontawesome.com/', 'body' => 'Icon font and CSS icons.'],
		['name' => 'Select2', 'version' => '4.0.13', 'license' => 'MIT', 'url' => 'https://select2.org/', 'body' => 'Enhanced select boxes.'],
		['name' => 'Perfect Scrollbar', 'version' => '1.5.6', 'license' => 'MIT', 'url' => 'https://github.com/mdbootstrap/perfect-scrollbar', 'body' => 'Custom scrollbars.'],
		['name' => 'jQuery Validation Engine', 'version' => '2.6.2', 'license' => 'MIT', 'url' => 'https://github.com/posabsolute/jQuery-Validation-Engine', 'body' => 'Form validation.'],
		['name' => 'PNotify', 'version' => '2.0.1', 'license' => 'GPL/LGPL/MPL', 'url' => 'https://sciactive.com/pnotify/', 'body' => 'Toast notifications.'],
		['name' => 'Bootbox.js', 'version' => '4.4.0', 'license' => 'MIT', 'url' => 'https://bootboxjs.com/', 'body' => 'Bootstrap modal dialogs.'],
		['name' => 'jQuery blockUI', 'version' => '', 'license' => 'MIT/GPL', 'url' => 'https://github.com/malsup/blockui', 'body' => 'Page element blocking during AJAX.'],
		['name' => 'jQuery PJAX', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/defunkt/jquery-pjax', 'body' => 'Partial page navigation.'],
		['name' => 'Autosize', 'version' => '1.14', 'license' => 'MIT', 'url' => 'https://github.com/jackmoore/autosize', 'body' => 'Auto-growing textareas.'],
		['name' => 'SlimScroll', 'version' => '1.3.6', 'license' => 'MIT/GPL', 'url' => 'https://github.com/rochal/jQuery-slimScroll', 'body' => 'Custom scroll areas.'],
		['name' => 'jQuery outside events', 'version' => '1.1', 'license' => 'MIT/GPL', 'url' => 'https://benalman.com/projects/jquery-outside-events-plugin/', 'body' => 'Detect clicks outside elements.'],
		['name' => 'jQuery placeholder', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/mathiasbynens/jquery-placeholder', 'body' => 'Placeholder polyfill for older browsers.'],
		['name' => 'jQuery hoverIntent', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/briancherne/jquery-hoverIntent', 'body' => 'Delayed hover events for menus.'],
		['name' => 'jStorage', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/julien-maurel/jStorage', 'body' => 'Client-side persistent storage wrapper.'],
		['name' => 'DOMPurify', 'version' => '3.4.10', 'license' => 'MPL-2.0', 'url' => 'https://github.com/cure53/DOMPurify', 'body' => 'Client-side HTML sanitization.'],
		['name' => 'Input Mask', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/RobinHerbots/Inputmask', 'body' => 'Input formatting masks.'],
		['name' => 'Mousetrap', 'version' => '1.5.2', 'license' => 'Apache-2.0', 'url' => 'https://github.com/ccampbell/mousetrap', 'body' => 'Keyboard shortcuts.'],
		['name' => 'Bootstrap Datepicker', 'version' => '', 'license' => 'Apache-2.0', 'url' => 'https://github.com/uxsolutions/bootstrap-datepicker', 'body' => 'Date picker (eternicode).'],
		['name' => 'Bootstrap ClockPicker', 'version' => '0.0.7', 'license' => 'MIT', 'url' => 'https://github.com/weareoutman/clockpicker', 'body' => 'Time picker widget.'],
		['name' => 'Date.js', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/dangrossman/bootstrap-daterangepicker', 'body' => 'Date parsing for daterangepicker.'],
	],
	'JavaScript & CSS — Feature libraries' => [
		['name' => 'CKEditor', 'version' => '4.22.1', 'license' => 'GPL/LGPL/MPL', 'url' => 'https://ckeditor.com/', 'body' => 'Rich text editor.'],
		['name' => 'CodeMirror', 'version' => '5.65.21', 'license' => 'MIT', 'url' => 'https://codemirror.net/', 'body' => 'Code editor for templates.'],
		['name' => 'js-beautify', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/beautifier/js-beautify', 'body' => 'HTML formatting in template editors.'],
		['name' => 'clipboard.js', 'version' => '1.5.16', 'license' => 'MIT', 'url' => 'https://github.com/zenorocha/clipboard.js', 'body' => 'Copy-to-clipboard buttons.'],
		['name' => 'DataTables', 'version' => '1.10.7', 'license' => 'MIT', 'url' => 'https://datatables.net/', 'body' => 'Sortable/filterable tables.'],
		['name' => 'FullCalendar', 'version' => '2.3.1', 'license' => 'MIT', 'url' => 'https://fullcalendar.io/', 'body' => 'Calendar views.'],
		['name' => 'Moment.js', 'version' => '2.9.0', 'license' => 'MIT', 'url' => 'https://momentjs.com/', 'body' => 'Date/time parsing for FullCalendar.'],
		['name' => 'Gridster', 'version' => '0.5.6', 'license' => 'MIT', 'url' => 'https://github.com/ducksboard/gridster.js', 'body' => 'Dashboard widget grid layout.'],
		['name' => 'jqPlot', 'version' => '1.0.8', 'license' => 'MIT/GPLv2', 'url' => 'https://www.jqplot.com/', 'body' => 'Charts on dashboards and reports.'],
		['name' => 'Flot', 'version' => '0.8.3', 'license' => 'MIT', 'url' => 'https://www.flotcharts.org/', 'body' => 'Charts in dashboards and detail views.'],
		['name' => 'BxSlider', 'version' => '4.1', 'license' => 'WTFPL', 'url' => 'https://bxslider.com/', 'body' => 'Image slider on Home dashboard.'],
		['name' => 'jsTree', 'version' => '3.2.1', 'license' => 'MIT', 'url' => 'https://www.jstree.com/', 'body' => 'Tree views (menu, knowledge base, categories).'],
		['name' => 'Color picker', 'version' => '', 'license' => 'MIT/GPL', 'url' => 'http://www.eyecon.ro/colorpicker/', 'body' => 'Color selection in settings.'],
		['name' => 'malihu custom scrollbar', 'version' => '2.8', 'license' => 'MIT', 'url' => 'https://github.com/malihu/malihu-custom-scrollbar-plugin', 'body' => 'Custom scrollbars in picklist dependency editor.'],
		['name' => 'Date picker for jQuery', 'version' => '4.1.0', 'license' => 'MIT/GPL', 'url' => 'https://github.com/kbwood/calendar', 'body' => 'Date picker in workflows and reports.'],
		['name' => 'Leaflet', 'version' => '1.0.0-rc.3', 'license' => 'BSD-2-Clause', 'url' => 'https://leafletjs.com/', 'body' => 'Interactive maps.'],
		['name' => 'Leaflet.markercluster', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/Leaflet/Leaflet.markercluster', 'body' => 'Map marker clustering.'],
		['name' => 'Leaflet.awesome-markers', 'version' => '2.0.1', 'license' => 'MIT', 'url' => 'https://github.com/lvoogdt/Leaflet.awesome-markers', 'body' => 'Font-based map markers.'],
		['name' => 'html2canvas', 'version' => '1.4.1', 'license' => 'MIT', 'url' => 'https://html2canvas.hertzen.com/', 'body' => 'Screenshot capture for issue reporting.'],
		['name' => 'jQuery Cycle', 'version' => '', 'license' => 'MIT/GPL', 'url' => 'https://jquery.malsup.com/cycle/', 'body' => 'Image cycling in Products detail.'],
		['name' => 'jQuery MultiFile', 'version' => '', 'license' => 'MIT', 'url' => 'https://github.com/fyneworks/jquery-multifile', 'body' => 'Multiple file upload widget.'],
	],
	'Development only' => [
		['name' => 'PHP Debug Bar', 'version' => '2.2.4', 'license' => 'MIT', 'url' => 'https://github.com/php-debugbar/php-debugbar', 'body' => 'Debug toolbar when DISPLAY_DEBUG_CONSOLE is enabled.'],
	],
];

$style = <<<'CSS'
<style>
	.homepage_link {
		padding-right: 10px;
		padding-top: 8px;
		color: #000AFF;
		font-size: 14px;
		font-weight: 800;
	}
	.credits-section {
		margin: 20px 0 10px;
		padding: 8px 12px;
		font-size: 16px;
		font-weight: 700;
		background: #f5f5f5;
		border-left: 4px solid #337ab7;
	}
</style>
CSS;

$out = $style . "\n<div class=\"bs-docs-example\">\n\t<div class=\"panel-group\" id=\"accordions\">\n";
$id = 0;

foreach ($credits as $section => $items) {
	$out .= "\t\t<div class=\"credits-section\">" . htmlspecialchars($section) . "</div>\n";
	foreach ($items as $item) {
		$label = $item['name'];
		if ($item['license'] !== '') {
			$label .= ' [' . $item['license'] . ']';
		}
		if ($item['version'] !== '') {
			$label .= ' - v' . $item['version'];
		}
		$collapseClass = $id === 0 ? 'collapse in' : 'collapse';
		$out .= "\t\t<div class=\"panel panel-default\">\n";
		$out .= "\t\t\t<div class=\"panel-heading\">\n";
		$out .= "\t\t\t\t<div class=\"panel-title\">\n";
		$out .= "\t\t\t\t\t<a class=\"pull-right homepage_link\" href=\"" . htmlspecialchars($item['url']) . "\">homepage</a>\n";
		$out .= "\t\t\t\t\t<a data-toggle=\"collapse\" data-parent=\"#accordions\" href=\"#p{$id}\">" . htmlspecialchars($label) . "</a>\n";
		$out .= "\t\t\t\t</div>\n";
		$out .= "\t\t\t</div>\n";
		$out .= "\t\t\t<div id=\"p{$id}\" class=\"panel-collapse {$collapseClass}\">\n";
		$out .= "\t\t\t\t<div class=\"panel-body\">" . htmlspecialchars($item['body']) . "</div>\n";
		$out .= "\t\t\t</div>\n";
		$out .= "\t\t</div>\n";
		++$id;
	}
}

$out .= "\t</div>\n</div>\n";

$target = dirname(__DIR__) . '/licenses/Credits.html';
file_put_contents($target, $out);
echo "Wrote {$target} ({$id} entries)\n";
