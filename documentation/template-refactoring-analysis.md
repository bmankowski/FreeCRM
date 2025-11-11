# Template Refactoring Analysis: Moving getCRMRecordType to Controllers

## Summary
Analysis of templates using `\App\Record::getType()` to determine which can easily move data preparation to controllers.

## Easy to Refactor (High Priority)

### 1. ExpiringSoldProductsContents.tpl
**Current:** `{assign var="CRMTYPE" value=\App\Record::getType($ROW.parent_id)}`
**Controller:** `src/Modules/Assets/Dashboards/ExpiringSoldProducts.php`
**Solution:** Enhance `getData()` method to add `parent_module` field to each row:
```php
public static function getData(\App\Http\Vtiger_Request $request, $widget)
{
    // ... existing code ...
    $data = $query->all();
    // Add module type for each row with parent_id
    foreach ($data as &$row) {
        if (!empty($row['parent_id'])) {
            $row['parent_module'] = \App\Record::getType($row['parent_id']);
        }
    }
    return $data;
}
```
**Template change:** `{assign var="CRMTYPE" value=$ROW.parent_module}`

### 2. Comment.tpl
**Current:** `{assign var=RELATED_MODULE value=\App\Record::getType($RELATED_TO)}`
**Controller:** `src/Modules/ModComments/Views/DetailAjax.php`
**Solution:** Get module type in controller and assign it:
```php
$relatedTo = $recordModel->get('related_to');
$relatedModule = $relatedTo ? \App\Record::getType($relatedTo) : null;
$viewer->assign('RELATED_MODULE', $relatedModule);
```
**Template change:** Remove the assign, use `{$RELATED_MODULE}` directly

### 3. Reminders.tpl & ActivityStateModal.tpl
**Current:** `{\App\Record::getType($RECORD->get('link'))}`
**Controller:** `src/Modules/Calendar/Views/Reminders.php`
**Solution:** Pre-process records to add link_module_name:
```php
foreach ($recordModels as $record) {
    $record->updateReminderStatus(2);
    $linkId = $record->get('link');
    if ($linkId) {
        $record->set('link_module_name', \App\Record::getType($linkId));
    }
    $colorList[$record->getId()] = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers($moduleName, $record->getId(), $record);
}
```
**Template change:** Use `{$RECORD->get('link_module_name')}` instead

## Medium Difficulty (Good Enhancement Opportunity)

### 4. EditViewInventoryItem.tpl & DetailViewInventoryView.tpl
**Current:** `{assign var="REFERENCE_MODULE" value=\App\Record::getType($ITEM_DATA['name'])}` and `{assign var="ROW_MODULE" value=\App\Record::getType($INVENTORY_ROW['name'])}`
**Controller:** Uses `$RECORD->getInventoryData()` from Record model
**Solution:** Enhance `getInventoryData()` in `src/Modules/Base/Models/Record.php` to include module name for inventory rows that reference other records:
```php
public static function getInventoryDataById($ID, $moduleName)
{
    // ... existing code ...
    $fields = [];
    while ($row = $db->fetch_array($result)) {
        // If this row has a 'name' field (reference to another record), add module name
        if (!empty($row['name'])) {
            $row['name_module'] = \App\Record::getType($row['name']);
        }
        $fields[] = $row;
    }
    return $fields;
}
```
**Template changes:** 
- EditViewInventoryItem.tpl: `{assign var="REFERENCE_MODULE" value=$ITEM_DATA.name_module}`
- DetailViewInventoryView.tpl: `{assign var="ROW_MODULE" value=$INVENTORY_ROW.name_module}`

**Benefits:** This enhancement would benefit ALL inventory views across the system, not just these templates.

## More Complex (Lower Priority)

### 5. RelatedListContents.tpl
**Current:** `{assign var="ROW_MODULE" value=\App\Record::getType($INVENTORY_ROW['name'])}`
**Controller:** `src/Modules/Base/Views/RelatedList.php`
**Note:** This is nested inside inventory data that's part of a related list. The inventory data structure is more complex here. If we implement the enhancement to `getInventoryData()` above, this would automatically benefit from it.

## Implementation Priority

1. **High Priority:** ExpiringSoldProductsContents.tpl, Comment.tpl (easy wins)
2. **Medium Priority:** Enhance `getInventoryData()` method (benefits multiple templates)
3. **Medium Priority:** Reminders.tpl & ActivityStateModal.tpl (Calendar module)
4. **Low Priority:** RelatedListContents.tpl (will benefit from #2)

## Benefits of Moving to Controllers

1. **Separation of Concerns:** Business logic stays in PHP, templates focus on presentation
2. **Performance:** Can batch process multiple records more efficiently
3. **Testability:** Easier to unit test controller logic
4. **Maintainability:** Centralized data preparation logic
5. **Consistency:** All templates use pre-prepared data, reducing template complexity

