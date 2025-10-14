{strip}
<!-- Test file for vtranslate refactoring -->
<h1>{vtranslate('LBL_TITLE')}</h1>
<h2>{vtranslate('LBL_SUBTITLE', 'Vtiger')}</h2>
<h3>{vtranslate({$MODULE_NAME}, 'Vtiger')}</h3>
<p>{vtranslate('LBL_DESCRIPTION', $MODULE_NAME, 'param1', 'param2')}</p>
<span alt="{vtranslate('LBL_TOOLTIP')}">Content</span>
<div data-content="{vtranslate('LBL_CONTENT', 'HelpInfo')}">Data</div>
{if vtranslate('LBL_CONDITION', 'Vtiger') eq 'test'}
    <p>Conditional content</p>
{/if}
{vtranslate($FIELD_MODEL->get('label'), $MODULE_NAME)}
{vtranslate($MODULE_NAME|cat:'|'|cat:$FIELD_MODEL->get('label'), 'HelpInfo')}
{/strip}
