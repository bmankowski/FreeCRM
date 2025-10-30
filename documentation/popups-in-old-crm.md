## Popups and Modals in FreeCRM (Vtiger-based)

This document explains how popup windows and modal windows work in the CRM, how they differ, and provides a full end-to-end trace of a real popup usage from a parent page through selection and data return. Code references below point directly to files and lines in this repository.

### Overview

- Popups (record selector popups)
  - Open in a separate browser window via `window.open`.
  - Initial content is server-rendered HTML; subsequent interactions inside the popup (search, sort, pagination) use AJAX.
  - Used for selecting/relating records (e.g., selecting an Account on a Contact).

- Modals (in-page overlays)
  - Open within the current page using a BlockUI overlay (styled similar to Bootstrap modals).
  - Content usually fetched via AJAX; fully in-page.
  - Used for quick forms, confirmations, and small utilities.

Both use AJAX, but in different places: popups use AJAX within the new window after initial load; modals fetch and display content directly on the current page.

---

## Popup architecture

### Server-side view and template

- The base popup controller initializes list data and renders `Popup.tpl` for the target module.

```36:46:/home/bmankowski/projects/FreeCRM/modules/Vtiger/views/Popup.php
function process (Vtiger_Request $request) {
    $viewer = $this->getViewer ($request);
    $moduleName = $this->getModule($request);
    ...
    $this->initializeListViewContents($request, $viewer);

    $viewer->assign('COMPANY_LOGO',$companyLogo);
    $viewer->view('Popup.tpl', $moduleName);
}
```

- The popup template provides a container for search UI, a content region that gets replaced via AJAX, and a hidden event name used for cross-window messaging.

```13:17:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/Popup.tpl
<div id="popupPageContainer" class="contentsDiv">
	<div class="paddingLeftRight10px">{include file='PopupSearch.tpl'|vtemplate_path:$MODULE}</div>
	<div id="popupContents" class="paddingLeftRight10px">{include file='PopupContents.tpl'|vtemplate_path:$MODULE_NAME}</div>
	<input type="hidden" class="triggerEventName" value="{$smarty.request.triggerEventName}"/>
</div>
```

### Client-side popup controller

- Opening a popup window: `Vtiger_Popup_Js.show(...)` builds a URL, injects a unique event name for the return message, and opens a new window.

```133:176:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/resources/Popup.js
show : function(urlOrParams, cb, windowName, eventName, onLoadCb){
    ...
    if (typeof urlOrParams == 'object') {
        urlOrParams['triggerEventName'] = eventName;
    } else {
        urlOrParams += '&triggerEventName=' + eventName;
    }

    var urlString = (typeof urlOrParams == 'string')? urlOrParams : jQuery.param(urlOrParams);
    var url = 'index.php?'+urlString;
    var popupWinRef =  window.open(url, windowName ,'width=900,height=650,resizable=0,scrollbars=1');
    ...
    if(typeof cb != 'undefined') {
        this.retrieveSelectedRecords(cb, eventName);
    }
    ...
    return popupWinRef;
},
```

- Wiring the callback in the parent window: listens for the specific event name and invokes the provided callback with the payload string.

```178:186:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/resources/Popup.js
retrieveSelectedRecords : function(cb, eventName) {
    if(typeof eventName == 'undefined') {
        eventName = 'postSelection';
    }

    jQuery.windowMsg(eventName, function(data) {
        cb(data);
    });
},
```

- Inside the popup window, list interactions are AJAX-driven; results replace `#popupContents`.

```395:407:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/resources/Popup.js
getPageRecords : function(params){
    ...
    Vtiger_BaseList_Js.getPageRecords(params).then(
        function(data){
            jQuery('#popupContents').html(data);
            ...
```

- Returning a selection from the popup: closes the window and triggers the parent event with a JSON string payload.

```243:271:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/resources/Popup.js
getListViewEntries: function(e){
    ...
    if(typeof dataUrl != 'undefined'){
        ...
        thisInstance.done(recordData, thisInstance.getEventName());
        ...
    } else {
        var id = row.data('id');
        var recordName = row.data('name');
        var recordInfo = row.data('info');
        var response ={};
        response[id] = {'name' : recordName,'info' : recordInfo} ;
        thisInstance.done(response, thisInstance.getEventName());
        e.preventDefault();
    }
},
```

```196:211:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/resources/Popup.js
done : function(result, eventToTrigger, window) {

    if(typeof eventToTrigger == 'undefined' || eventToTrigger.length <=0 ) {
        eventToTrigger = 'postSelection'
    }

    if(typeof window == 'undefined'){
        window = self;
    }
    window.close();
    var data = JSON.stringify(result);
    data = data.replace(/\$\$/g,"$ $");

    jQuery.triggerParentEvent(eventToTrigger, data);

},
```

---

## Full end-to-end example: selecting a reference (e.g., Account) from an Edit form

Use case: On a module edit form with a reference field (e.g., `account_id`), the user clicks the search icon to pick an Account.

1) Parent page assembles popup parameters from the field context and current record.

```89:116:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/resources/Edit.js
getPopUpParams : function(container) {
    var params = {};
    var sourceModule = app.getModuleName();
    var popupReferenceModule = jQuery('input[name="popupReferenceModule"]',container).val();
    var sourceFieldElement = jQuery('input[class="sourceField"]',container);
    var sourceField = sourceFieldElement.attr('name');
    var sourceRecordElement = jQuery('input[name="record"]');
    var sourceRecordId = '';
    if(sourceRecordElement.length > 0) {
        sourceRecordId = sourceRecordElement.val();
    }
    ...
    var params = {
        'module' : popupReferenceModule,
        'src_module' : sourceModule,
        'src_field' : sourceField,
        'src_record' : sourceRecordId
    }
    ...
    return params;
},
```

2) Parent page opens the popup and registers a callback to receive the selection.

```119:160:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/resources/Edit.js
openPopUp : function(e){
    ...
    var popupInstance =Vtiger_Popup_Js.getInstance();
    popupInstance.show(params,function(data){
        var responseData = JSON.parse(data);
        var dataList = new Array();
        for(var id in responseData){
            var data = {
                'name' : responseData[id].name,
                'id' : id
            }
            dataList.push(data);
            if(!isMultiple) {
                thisInstance.setReferenceFieldValue(parentElem, data);
            }
        }
        ...
    });
},
```

3) Server returns the popup page rendered via `Popup.tpl`; user searches/filters (AJAX) and clicks a row to select.

4) Popup closes and sends a JSON payload (e.g., `{"11":{"name":"Chemex Inc"}}`) back to the parent using the event name.

5) Parent consumes the selection: sets the hidden id and read-only display, triggers events, and optionally auto-fills mapped fields.

```162:205:/home/bmankowski/projects/FreeCRM/layouts/vlayout/modules/Vtiger/resources/Edit.js
setReferenceFieldValue : function(container, params) {
    var sourceField = container.find('input[class="sourceField"]').attr('name');
    var fieldElement = container.find('input[name="'+sourceField+'"]');
    var sourceFieldDisplay = sourceField+"_display";
    var fieldDisplayElement = container.find('input[name="'+sourceFieldDisplay+'"]');
    var popupReferenceModule = container.find('input[name="popupReferenceModule"]').val();

    var selectedName = params.name;
    var id = params.id;

    fieldElement.val(id)
    fieldDisplayElement.val(selectedName).attr('readonly',true);
    fieldElement.trigger(Vtiger_Edit_Js.referenceSelectionEvent, {'source_module' : popupReferenceModule, 'record' : id, 'selectedName' : selectedName});
    ...
    this.getRecordDetails(data).then(
        function(data){
            var response = data['result']['data'];
            $.each( mappingRelatedField, function( key, value ) {
                if( response[value[0]] != 0){
                    var mapFieldElement = formElement.find('input[name="'+key+'"]');
                    if(mapFieldElement.length > 0){
                        mapFieldElement.val(response[value[0]]);
                    }
                    var mapFieldDisplayElement = formElement.find('input[name="'+key+'_display"]');
                    if(mapFieldDisplayElement.length > 0){
                        mapFieldDisplayElement.val(response[value[0]+'_label']).attr('readonly',true);
                        ...
                    }
                }
            });
        }
    );
},
```

---

## Where `Popup.tpl` is used

Multiple controllers render `Popup.tpl` (or specialized derived templates) as the base for record selector popups; examples include:

```43:46:/home/bmankowski/projects/FreeCRM/modules/Vtiger/views/Popup.php
$viewer->view('Popup.tpl', $moduleName);
```

```25:26:/home/bmankowski/projects/FreeCRM/modules/Vtiger/views/EmailsRelatedModulePopup.php
$viewer->view('Popup.tpl', $moduleName);
```

```41:41:/home/bmankowski/projects/FreeCRM/modules/Settings/Roles/views/Popup.php
$viewer->view('Popup.tpl', $qualifiedModuleName);
```

```32:33:/home/bmankowski/projects/FreeCRM/modules/Inventory/views/ServicesPopup.php
$viewer->view('Popup.tpl', $moduleName);
```

```33:34:/home/bmankowski/projects/FreeCRM/modules/Inventory/views/ProductsPopup.php
$viewer->view('Popup.tpl', $moduleName);
```

```33:34:/home/bmankowski/projects/FreeCRM/modules/Import/views/List.php
$viewer->view('Popup.tpl', $moduleName);
```

---

## Modals vs Popups

Modals are opened via the global `app.showModalWindow(...)`, which uses AJAX to fetch content (if a URL is provided) and uses BlockUI to display it as an overlay. This is separate from the popup window approach.

```238:259:/home/bmankowski/projects/FreeCRM/layouts/vlayout/resources/app.js
showModalWindow: function(data, url, cb, css) {
    ...
    if (data) {
        showModalData(data)
    } else {
        jQuery.get(url).then(function(response){
            showModalData(response);
        });
    }
```

```305:317:/home/bmankowski/projects/FreeCRM/layouts/vlayout/resources/app.js
jQuery.blockUI({
    'message' : container,
    'overlayCSS' : effectiveOverlayCss,
    'css' : effectiveCss,
    bindEvents: false,
    applyPlatformOpacityRules : false
});
```

Differences:
- Popups: new window; server-rendered `Popup.tpl`; internal interactions via AJAX; selection returned via cross-window messaging and the popup window closes.
- Modals: same page; opened via BlockUI; content usually fetched via AJAX; dismissed without leaving current page.

---

## Developer notes and tips

- To add or adjust popup behavior for a module:
  - Server: extend/adjust `modules/<Module>/views/Popup.php` (inherits from `Vtiger_Popup_View`) to prepare headers, entries, and search parameters.
  - Template: update `layouts/vlayout/modules/<Module>/PopupContents.tpl` and `PopupSearch.tpl` for layout/fields.
  - Client: customize list logic in `layouts/vlayout/modules/Vtiger/resources/Popup.js` if needed.

- To use a popup from a form field:
  - Ensure the field container includes `input.sourceField`, `input[name="popupReferenceModule"]`, and the trigger element with `.relatedPopup`.
  - The base edit controller (`Vtiger_Edit_Js`) already wires `.relatedPopup` clicks to `openPopUp`.

- To use a modal:
  - Call `app.showModalWindow(dataOrParams)` with an HTML string or a URL; optional `cb`, `css`, and `overlayCss` are supported.

- Communication back to the parent (popups):
  - Use `Vtiger_Popup_Js.done(result, eventName)` to close and deliver data; the parent’s `show(...)` call wires the listener via `retrieveSelectedRecords`.

---

## Quick checklist

- Popup needs: server controller → `Popup.tpl` → `PopupSearch.tpl` + `PopupContents.tpl` → client `Popup.js` → messaging via `triggerEventName` → parent callback.
- Modal needs: `app.showModalWindow(...)` with data or URL; content initializes standard UI elements (select2/date/time) automatically.


