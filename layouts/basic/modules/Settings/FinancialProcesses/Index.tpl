{strip}
<!-- layouts/basic/modules/Settings/FinancialProcesses/Index.tpl -->
{extends file="MainLayout.tpl"|@vtemplate_path}

{block name="content"}
    <div class="mainContainer">
        <div class="contentsDiv">
            {include file='IndexContent.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
        </div> <!-- close contentsDiv -->
    </div> <!-- close mainContainer -->
{/block}
<!--/layouts/basic/modules/Settings/FinancialProcesses/Index.tpl -->
{/strip}

