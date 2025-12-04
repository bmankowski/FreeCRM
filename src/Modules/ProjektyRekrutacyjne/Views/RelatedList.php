<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\ProjektyRekrutacyjne\Views;

/**
 * RelatedList View for ProjektyRekrutacyjne module.
 * 
 * Displays related records for recruitment projects.
 */
class RelatedList extends \App\Modules\Base\Views\RelatedList
{
    /**
     * Process.
     *
     * @param \App\Http\Vtiger_Request $request
     * @return string
     */
    public function process(\App\Http\Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $relatedModuleName = $request->getByType('relatedModule', 2);
        $parentId = $request->getInteger('record');

        // Determine related view - default to ListPreview for Kandydaci
        $relatedView = $request->get('relatedView');
        if (empty($relatedView)) {
            if (empty($_SESSION['relatedView'][$moduleName][$relatedModuleName])) {
                // If not set in session, set the default view
                if ($moduleName == "ProjektyRekrutacyjne" && $relatedModuleName == "Kandydaci") {
                    $relatedView = 'ListPreview';
                } else {
                    $relatedView = 'List';
                }
            } else {
                $relatedView = $_SESSION['relatedView'][$moduleName][$relatedModuleName];
            }
        } else {
            $_SESSION['relatedView'][$moduleName][$relatedModuleName] = $relatedView;
        }

        $pageNumber = $request->getInteger('page');
        if (empty($pageNumber)) {
            $pageNumber = 1;
        }
        $totalCount = $request->getInteger('totalCount');
        
        $pagingModel = new \App\Modules\Base\Models\Paging();
        $pagingModel->set('page', $pageNumber);
        if ($request->has('limit')) {
            $pagingModel->set('limit', $request->getInteger('limit'));
        }

        $label = $request->get('tab_label');
        $parentRecordModel = \App\Modules\Base\Models\Record::getInstanceById($parentId, $moduleName);
        $relationListView = \App\Modules\Base\Models\RelationListView::getInstance($parentRecordModel, $relatedModuleName, $label);

        // Handle ordering
        $orderBy = $request->get('orderby');
        $sortOrder = $request->get('sortorder');
        if ($sortOrder == 'ASC') {
            $nextSortOrder = 'DESC';
            $sortImage = 'glyphicon glyphicon-chevron-down';
        } else {
            $nextSortOrder = 'ASC';
            $sortImage = 'glyphicon glyphicon-chevron-up';
        }
        if (empty($orderBy) && empty($sortOrder)) {
            $relatedInstance = \App\Core\CRMEntity::getInstance($relatedModuleName);
            $orderBy = $relatedInstance->default_order_by;
            $sortOrder = $relatedInstance->default_sort_order;
        }
        if (!empty($orderBy)) {
            $relationListView->set('orderby', $orderBy);
            $relationListView->set('sortorder', $sortOrder);
        }

        if ($request->has('entityState')) {
            $relationListView->set('entityState', $request->getByType('entityState'));
        }

        $viewer = $this->getViewer($request);
        $searchKey = $request->get('search_key');
        $searchValue = $request->get('search_value');
        $operator = $request->get('operator');
        if (!empty($operator)) {
            $relationListView->set('operator', $operator);
        }
        $viewer->assign('OPERATOR', $operator);
        $viewer->assign('ALPHABET_VALUE', $searchValue);
        
        if (!empty($searchKey) && !empty($searchValue)) {
            $relationListView->set('search_key', $searchKey);
            $relationListView->set('search_value', $searchValue);
        }

        // Handle search params
        $searchParams = $request->get('search_params');
        if (empty($searchParams) || !\is_array($searchParams)) {
            $searchParams = [];
        }
        
        $transformedSearchParams = $relationListView->get('query_generator')->parseBaseSearchParamsToCondition($searchParams);
        $relationListView->set('search_params', $transformedSearchParams);

        // To make smarty to get the details easily accessible
        foreach ($searchParams as $fieldListGroup) {
            foreach ($fieldListGroup as $fieldSearchInfo) {
                $fieldSearchInfo['searchValue'] = $fieldSearchInfo[2] ?? '';
                $fieldSearchInfo['fieldName'] = $fieldName = $fieldSearchInfo[0] ?? '';
                $fieldSearchInfo['specialOption'] = $fieldSearchInfo[3] ?? '';
                $searchParams[$fieldName] = $fieldSearchInfo;
            }
        }

        // Get records
        $models = $relationListView->getEntries($pagingModel);
        $links = $relationListView->getLinks();
        $header = $relationListView->getHeaders();
        $noOfEntries = count($models);

        $relationModel = $relationListView->getRelationModel();
        $relatedModuleModel = $relationModel->getRelationModuleModel();
        $relationField = $relationModel->getRelationField();

        // Color list
        $colorList = [];
        foreach ($models as &$record) {
            $colorList[$record->getId()] = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers($relatedModuleName, $record->getId(), $record);
        }

        $viewer->assign('COLOR_LIST', $colorList);
        $viewer->assign('VIEW_MODEL', $relationListView);
        $viewer->assign('RELATED_RECORDS', $models);
        $viewer->assign('PARENT_RECORD', $parentRecordModel);
        $viewer->assign('RELATED_LIST_LINKS', $links);
        $viewer->assign('RELATED_HEADERS', $header);
        $viewer->assign('RELATED_MODULE', $relatedModuleModel);
        $viewer->assign('RELATED_MODULE_NAME', $relatedModuleName);
        $viewer->assign('RELATED_ENTIRES_COUNT', $noOfEntries);
        $viewer->assign('RELATION_FIELD', $relationField);
        $viewer->assign('RELATED_VIEW', $relatedView);

        if (\App\Core\AppConfig::performance('LISTVIEW_COMPUTE_PAGE_COUNT')) {
            $totalCount = $relationListView->getRelatedEntriesCount();
        }
        if (!empty($totalCount)) {
            $pagingModel->set('totalCount', (int) $totalCount);
            $viewer->assign('LISTVIEW_COUNT', $totalCount);
            $viewer->assign('TOTAL_ENTRIES', $totalCount);
        } else {
            $viewer->assign('LISTVIEW_COUNT', 0);
            $viewer->assign('TOTAL_ENTRIES', 0);
        }

        $pageCount = $pagingModel->getPageCount();
        $startPaginFrom = $pagingModel->getStartPagingFrom();

        $viewer->assign('PAGE_COUNT', $pageCount);
        $viewer->assign('PAGE_NUMBER', $pageNumber);
        $viewer->assign('START_PAGIN_FROM', $startPaginFrom);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('PAGING_MODEL', $pagingModel);
        $viewer->assign('ORDER_BY', $orderBy);
        $viewer->assign('SORT_ORDER', $sortOrder);
        $viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
        $viewer->assign('SORT_IMAGE', $sortImage);
        $viewer->assign('COLUMN_NAME', $orderBy);
        $viewer->assign('INVENTORY_FIELDS', $relationModel->getRelationInventoryFields());
        $viewer->assign('SHOW_CREATOR_DETAIL', $relationModel->showCreatorDetail());
        $viewer->assign('SHOW_COMMENT', $relationModel->showComment());

        // Handle favorites
        $isFavorites = false;
        if ($relationModel->isFavorites() && \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'FavoriteRecords')) {
            $favorites = $relationListView->getFavoriteRecords();
            $viewer->assign('FAVORITES', $favorites);
            $isFavorites = $relationModel->isFavorites();
        }
        $viewer->assign('IS_FAVORITES', $isFavorites);
        $viewer->assign('IS_EDITABLE', $relationModel->isEditable());
        $viewer->assign('IS_DELETABLE', $relationModel->isDeletable());
        $viewer->assign('USER_MODEL', $request->getUser());

        // Ensure search details exist for all headers to avoid undefined index notices in templates
        if (is_array($header)) {
            foreach ($header as $headerField) {
                $headerName = $headerField->getName();
                if (!isset($searchParams[$headerName])) {
                    $searchParams[$headerName] = ['searchValue' => '', 'fieldName' => $headerName];
                }
            }
        }
        $viewer->assign('SEARCH_DETAILS', $searchParams);
        $viewer->assign('SEARCH_PARAMS', $searchParams);
        $viewer->assign('LOCKED_EMPTY_FIELDS', []);
        $viewer->assign('SHOW_HEADER', true);
        $viewer->assign('CUSTOM_VIEW_LIST', []);
        $viewer->assign('VIEW', $request->get('view'));
        $viewer->assign('IS_CREATE_PERMITTED', \App\Modules\Users\Models\Privileges::isPermitted($relatedModuleName, 'CreateView'));
        $viewer->assign('IS_WIDGETS', false);

        // Prepare data for RelatedListLeftSide template - move function calls from templates to controller
        $this->prepareRelatedListLeftSideData($viewer, $models, $relatedModuleModel, $request->getUser());
        
        // Prepare data for RelatedList template - move function calls from templates to controller
        $viewer->assign('AUTO_REFRESH_LIST_ON_CHANGE', \App\Core\AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE'));

        return $viewer->view('RelatedList.tpl', $moduleName, true);
    }
}
