{*<!-- {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} --!>*}
{if $STACK_TRACE}
<!-- layouts/basic/modules/Base/ErrorStackTraceButton.tpl -->
	<button type="button" class="btn btn-link btn-sm" id="copyStackTraceBtn" title="{"LBL_COPY_STACK_TRACE"|t}" style="padding: 0;">
		<span class="fas fa-copy" aria-hidden="true"></span> {"LBL_COPY_STACK_TRACE"|t}
	</button>
	<textarea id="stackTraceData" style="position:absolute;left:-9999px;width:1px;height:1px" readonly>{$STACK_TRACE|escape:'html'}</textarea>
	{literal}
	<script type="text/javascript">
		(function () {
			var btn = document.getElementById('copyStackTraceBtn');
			var ta = document.getElementById('stackTraceData');
			if (!btn || !ta) {
				return;
			}
			var defaultHtml = btn.innerHTML;
			btn.addEventListener('click', function () {
				var copiedLabel = app.vtranslate('JS_NOTIFY_COPY_TEXT');
				var text = ta.value;
				var showCopied = function () {
					btn.innerHTML = '<span class="fas fa-check" aria-hidden="true"></span> ' + copiedLabel;
					setTimeout(function () { btn.innerHTML = defaultHtml; }, 2000);
				};
				navigator.clipboard.writeText(text).then(showCopied);
			});
		}());
	</script>
	{/literal}
<!--/layouts/basic/modules/Base/ErrorStackTraceButton.tpl -->
{/if}
