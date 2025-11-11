<?php

namespace App\Modules\Settings\Dashboard\Views;


/*********************************************************************************
FreeCRM - Customer Relationship Management System
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
*
********************************************************************************/

class Index extends \App\Modules\Settings\Base\Views\Index
{

    public function __construct()
    {
        \App\Modules\Settings\Base\Models\Tracker::addBasic('view');
        parent::__construct();
        $this->exposeMethod('index');
        $this->exposeMethod('github');
        $this->exposeMethod('systemWarnings');
        $this->exposeMethod('getWarningsList');
    }

    public function checkPermission(\App\Http\Vtiger_Request $request)
    {
        $currentUserModel = $request->getUser();
        if (!$currentUserModel->isAdminUser()) {
            throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
        }
    }

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			// AJAX mode requests - return partial content
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		
		// Initial page load - render full page with MainLayout
		$viewer = $this->getViewer($request);
		$qualifiedModuleName = $request->getModule(false);
		
		// Prepare initial data for tabs (needed for warnings count)
		$warnings = \App\SystemWarnings::getWarnings('all');
		$warningsCount = count($warnings);
		$viewer->assign('WARNINGS_COUNT', $warningsCount);
		
		// Assign initial dashboard data for IndexContent.tpl
		$usersCount = \App\Modules\Users\Models\Record::getCount(true);
		$allWorkflows = \App\Modules\Settings\Workflows\Models\Record::getAllAmountWorkflowsAmount();
		$activeModules = \App\Modules\Settings\ModuleManager\Models\Module::getModulesCount(true);
		$pinnedSettingsShortcuts = \App\Modules\Settings\Base\Models\MenuItem::getPinnedItems();
		
		$viewer->assign('WARNINGS', !\App\Http\Vtiger_Session::has('SystemWarnings') ? $warnings : []);
		$viewer->assign('USERS_COUNT', $usersCount);
		$viewer->assign('ALL_WORKFLOWS', $allWorkflows);
		$viewer->assign('ACTIVE_MODULES', $activeModules);
		$viewer->assign('SETTINGS_SHORTCUTS', $pinnedSettingsShortcuts);
		
		// Prepare Dashboard-specific data for Index template
		$this->prepareDashboardData($viewer, $warningsCount);
		
		$viewer->view('Index.tpl', $qualifiedModuleName);
	}
	

    /**
     * Index - AJAX content for index tab
     * @param \App\Http\Vtiger_Request $request
     */
    public function index(\App\Http\Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);
        $usersCount = \App\Modules\Users\Models\Record::getCount(true);
        $allWorkflows = \App\Modules\Settings\Workflows\Models\Record::getAllAmountWorkflowsAmount();
        $activeModules = \App\Modules\Settings\ModuleManager\Models\Module::getModulesCount(true);
        $pinnedSettingsShortcuts = \App\Modules\Settings\Base\Models\MenuItem::getPinnedItems();
        $warnings = \App\SystemWarnings::getWarnings('all');
        $warningsCount = count($warnings);

        $viewer->assign('WARNINGS_COUNT', $warningsCount);
        $viewer->assign('WARNINGS', !\App\Http\Vtiger_Session::has('SystemWarnings') ? $warnings : []);
        $viewer->assign('USERS_COUNT', $usersCount);
        $viewer->assign('ALL_WORKFLOWS', $allWorkflows);
        $viewer->assign('ACTIVE_MODULES', $activeModules);
        $viewer->assign('SETTINGS_SHORTCUTS', $pinnedSettingsShortcuts);
        
        // Prepare Dashboard-specific data for IndexContent template
        $this->prepareDashboardData($viewer, $warningsCount);
        
        $viewer->view('IndexContent.tpl', $qualifiedModuleName);
    }
    
    /**
     * Prepare data for Dashboard Index template
     * Moves function calls from templates to controller for better MVC separation
     */
    protected function prepareDashboardData($viewer, $warningsCount)
    {
        // Prepare JSON-encoded warnings count for tab data-params
        $warningsCountJson = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode(['count' => $warningsCount]));
        $viewer->assign('WARNINGS_COUNT_JSON', $warningsCountJson);
        
        // Prepare JSON-encoded warnings count for tab params
        $warningsParamsJson = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode(['count' => $warningsCount]));
        $viewer->assign('WARNINGS_PARAMS_JSON', $warningsParamsJson);
        
        // Prepare module names for settings shortcuts
        $settingsShortcuts = $viewer->getTemplateVars('SETTINGS_SHORTCUTS');
        $shortcutModuleNames = [];
        if ($settingsShortcuts) {
            foreach ($settingsShortcuts as $shortcut) {
                $linkto = $shortcut->get('linkto');
                $shortcutModuleNames[$shortcut->getId()] = \App\Modules\Base\Models\Menu::getModuleNameFromUrl($linkto);
            }
        }
        $viewer->assign('SHORTCUT_MODULE_NAMES', $shortcutModuleNames);
    }

    public function github(\App\Http\Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = 'Settings:Github';
        $clientModel = \App\Modules\Settings\Github\Models\Client::getInstance();
        $isAuthor = $request->get('author');
        $isAuthor = $isAuthor == 'true' ? true : false;
        $pageNumber = $request->get('page');
        if (empty($pageNumber)) {
            $pageNumber = 1;
        }

        $state = empty($request->get('state')) ? 'open' : $request->get('state');
        $issues = $clientModel->getAllIssues($pageNumber, $state, $isAuthor);
        $pagingModel = new \App\Modules\Base\Models\Paging();
        $pagingModel->set('page', $pageNumber);
        $pagingModel->set('totalCount', \App\Modules\Settings\Github\Models\Issues::$totalCount);

        $pageCount = $pagingModel->getPageCount();
        $startPaginFrom = $pagingModel->getStartPagingFrom();

        $viewer->assign('IS_AUTHOR', $isAuthor);
        $viewer->assign('PAGE_NUMBER', $pageNumber);
        $viewer->assign('ISSUES_STATE', $state);
        $viewer->assign('PAGE_COUNT', $pageCount);
        $viewer->assign('LISTVIEW_ENTRIES_COUNT', false);
        $viewer->assign('LISTVIEW_COUNT', \App\Modules\Settings\Github\Models\Issues::$totalCount);
        $viewer->assign('START_PAGIN_FROM', $startPaginFrom);
        $viewer->assign('PAGING_MODEL', $pagingModel);
        $viewer->assign('MODULE', $qualifiedModuleName);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->assign('GITHUB_ISSUES', $issues);
        $viewer->assign('GITHUB_CLIENT_MODEL', $clientModel);
        $viewer->view('Github.tpl', $qualifiedModuleName);
    }

    /**
     * Displays warnings system
     * 
     * @param \App\Http\Vtiger_Request $request
     */
    public function systemWarnings(\App\Http\Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);

        $folders = array_values(\App\SystemWarnings::getFolders());
        $foldersJson = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Json::encode($folders));
        $viewer->assign('MODULE', $qualifiedModuleName);
        $viewer->assign('FOLDERS_JSON', $foldersJson);
        $viewer->view('SystemWarnings.tpl', $qualifiedModuleName);
    }

    /**
     * Displays a list of system warnings
     * 
     * @param \App\Http\Vtiger_Request $request
     */
    public function getWarningsList(\App\Http\Vtiger_Request $request)
    {
        $folder = $request->get('folder');
        $active = $request->getBoolean('active');
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);

        $list = \App\SystemWarnings::getWarnings($folder, $active);
        $viewer->assign('MODULE', $qualifiedModuleName);
        $viewer->assign('WARNINGS_LIST', $list);
        $viewer->view('SystemWarningsList.tpl', $qualifiedModuleName);
    }

    /**
     * Function to get the list of Script models to be included
     * @param \App\Http\Vtiger_Request $request
     * @return <Array> - List of ScriptAsset instances
     */
    public function getFooterScripts(\App\Http\Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getFooterScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = array(
            'modules.Base.resources.Vtiger',
            'libraries.jquery.ckeditor.ckeditor',
            'libraries.jquery.ckeditor.adapters.jquery',
            'libraries.jquery.jstree.jstree',
            '~libraries/jquery/datatables/media/js/jquery.dataTables.js',
            '~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.js',
            'modules.Base.resources.CkEditor',
            'modules.Settings.Vtiger.resources.Vtiger',
            'modules.Settings.Vtiger.resources.Edit',
            "modules.Settings.$moduleName.resources.$moduleName",
            'modules.Settings.Vtiger.resources.Index',
            "modules.Settings.$moduleName.resources.Index",
        );

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        return array_merge($headerScriptInstances, $jsScriptInstances);
    }

    /**
     * Retrieves css styles that need to loaded in the page
     * @param \App\Http\Vtiger_Request $request - request model
     * @return <array> - array of StyleAsset
     */
    public function getHeaderCss(\App\Http\Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = array(
            'libraries.jquery.jstree.themes.proton.style',
            '~libraries/jquery/datatables/media/css/jquery.dataTables_themeroller.css',
            '~libraries/jquery/datatables/plugins/integration/bootstrap/3/dataTables.bootstrap.css',
        );
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        return array_merge($cssInstances, $headerCssInstances);
    }

    public function validateRequest(\App\Http\Vtiger_Request $request)
    {
        $request->validateReadAccess();
    }
}

