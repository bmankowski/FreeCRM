# Views NOT Using MainLayout.tpl

This document lists all view templates that do not yet extend `MainLayout.tpl`.

## ✅ Already Using MainLayout.tpl (8 templates)

1. ✓ `Base/DashBoard.tpl`
2. ✓ `Base/DetailView.tpl`
3. ✓ `Base/EditView.tpl`
4. ✓ `Base/Index.tpl`
5. ✓ `Base/ListView.tpl`
6. ✓ `Base/TreeRecords.tpl`
7. ✓ `Base/FindDuplicates.tpl`
8. ✓ `Calendar/CalendarView.tpl` **(JUST FIXED)**

---

## ❌ NOT Using MainLayout.tpl Yet

### 1. Calendar Views (Similar to the one we just fixed)

These are full-page calendar views that should probably extend MainLayout.tpl:

- ✗ `OSSTimeControl/CalendarView.tpl`
  - **Module**: OSSTimeControl (Time Control)
  - **Type**: Calendar view for time tracking
  - **Status**: Missing menu/navigation (same issue as Calendar)
  
- ✗ `Reservations/CalendarView.tpl`
  - **Module**: Reservations
  - **Type**: Calendar view for reservations
  - **Status**: Missing menu/navigation (same issue as Calendar)

- ✗ `Calendar/SharedCalendarView.tpl`
  - **Module**: Calendar
  - **Type**: Shared calendar view
  - **Status**: May need investigation

### 2. Module Index Pages

- ✗ `Home/Index.tpl`
  - **Module**: Home
  - **Type**: Homepage/Dashboard
  - **Content**: Shows "Homepage components" message
  - **Status**: Very minimal, might be deprecated or needs MainLayout
  
- ✗ `AJAXChat/Index.tpl`
  - **Module**: AJAXChat
  - **Type**: Chat interface (iframe)
  - **Status**: Embeds chat in iframe, may intentionally not use MainLayout

### 3. Export Views

These are export forms/pages:

- ✗ `Base/Export.tpl`
  - **Module**: Base (all modules inherit)
  - **Type**: Export form/wizard
  - **Status**: Form page, should probably extend MainLayout
  
- ✗ `Base/ExportPDF.tpl`
  - **Module**: Base
  - **Type**: PDF export interface
  - **Status**: May need MainLayout
  
- ✗ `Calendar/Export.tpl`
  - **Module**: Calendar
  - **Type**: Calendar-specific export
  - **Status**: Likely similar to Base/Export.tpl
  
- ✗ `Users/Export.tpl`
  - **Module**: Users
  - **Type**: User export interface
  - **Status**: May need MainLayout

### 4. Import Views

These are import wizard pages:

- ✗ `Import/ImportBasicStep.tpl`
  - **Module**: Import (all modules)
  - **Type**: Import wizard - Step 1
  - **Status**: Full-page wizard, should extend MainLayout
  
- ✗ `Import/ImportAdvanced.tpl`
  - **Module**: Import
  - **Type**: Import wizard - Advanced step
  - **Status**: Full-page wizard, should extend MainLayout
  
- ✗ `Import/ImportDetails.tpl`
  - **Module**: Import
  - **Type**: Import details step
  - **Status**: Full-page wizard, should extend MainLayout
  
- ✗ `Import/ImportResult.tpl`
  - **Module**: Import
  - **Type**: Import results page
  - **Status**: Full-page, should extend MainLayout
  
- ✗ `Import/ImportSchedule.tpl`
  - **Module**: Import
  - **Type**: Import scheduling interface
  - **Status**: Full-page, should extend MainLayout
  
- ✗ `Import/ImportStatus.tpl`
  - **Module**: Import
  - **Type**: Import status page
  - **Status**: Full-page, should extend MainLayout
  
- ✗ `Import/ImportUndoResult.tpl`
  - **Module**: Import
  - **Type**: Import undo results
  - **Status**: Full-page, should extend MainLayout
  
- ✗ `Import/ImportError.tpl`
  - **Module**: Import
  - **Type**: Import error page
  - **Status**: Full-page, should extend MainLayout

- ✗ `Calendar/Import.tpl`
  - **Module**: Calendar
  - **Type**: Calendar import
  - **Status**: May need MainLayout
  
- ✗ `PaymentsIn/Import.tpl`
  - **Module**: PaymentsIn
  - **Type**: Payment import
  - **Status**: May need MainLayout
  
- ✗ `PaymentsOut/Import.tpl`
  - **Module**: PaymentsOut
  - **Type**: Payment import
  - **Status**: May need MainLayout

### 5. Module-Specific Views

- ✗ `Portal/DetailView.tpl`
  - **Module**: Portal
  - **Type**: Portal record detail view
  - **Status**: Should extend MainLayout for consistency
  
- ✗ `CustomView/EditView.tpl`
  - **Module**: CustomView (Filters)
  - **Type**: Filter/view editor
  - **Status**: May be modal or full-page, needs investigation

### 6. KnowledgeBase Special Views

- ✗ `KnowledgeBase/PageView.tpl`
  - **Module**: KnowledgeBase
  - **Type**: Knowledge article page view
  - **Status**: May be intentionally minimal (popup/embed)
  
- ✗ `KnowledgeBase/PresentationView.tpl`
  - **Module**: KnowledgeBase
  - **Type**: Presentation/slideshow mode
  - **Status**: May be intentionally full-screen (no navigation)
  
- ✗ `KnowledgeBase/ContentsView.tpl`
  - **Module**: KnowledgeBase
  - **Type**: Contents wrapper
  - **Status**: Very minimal wrapper, likely used in DetailView

### 7. OSSMailView

- ✗ `OSSMailView/index.tpl`
  - **Module**: OSSMailView
  - **Type**: Mail view interface
  - **Status**: Tree view interface, should probably extend MainLayout
  
- ✗ `OSSMailView/MailsPreview.tpl`
  - **Module**: OSSMailView
  - **Type**: Email preview
  - **Status**: Likely AJAX partial
  
- ✗ `OSSMailView/preview.tpl`
  - **Module**: OSSMailView
  - **Type**: Email preview
  - **Status**: Likely AJAX partial
  
- ✗ `OSSMailView/sview.tpl`
  - **Module**: OSSMailView
  - **Type**: Special view
  - **Status**: Needs investigation

### 8. OSSMail

- ✗ `OSSMail/index.tpl`
  - **Module**: OSSMail
  - **Type**: Mail client (Roundcube iframe)
  - **Status**: Embeds Roundcube, may intentionally not use MainLayout

---

## Priority Ranking

### High Priority (Similar to Calendar issue - missing navigation)

1. **OSSTimeControl/CalendarView.tpl** - Same issue as Calendar/CalendarView
2. **Reservations/CalendarView.tpl** - Same issue as Calendar/CalendarView
3. **Base/Export.tpl** - Common export functionality
4. **Import/ImportBasicStep.tpl** and other Import wizards - Multi-step workflows
5. **Home/Index.tpl** - Homepage should have navigation

### Medium Priority

6. **Portal/DetailView.tpl** - Consistency with other DetailViews
7. **OSSMailView/index.tpl** - If it's a full-page view
8. **CustomView/EditView.tpl** - If it's not a modal

### Low Priority / May Be Intentional

9. **KnowledgeBase views** - May be intentionally minimal for embedding
10. **AJAXChat/Index.tpl** - iframe-based, may be intentional
11. **OSSMail/index.tpl** - Roundcube iframe, may be intentional
12. **Calendar/SharedCalendarView.tpl** - Needs investigation

---

## Notes

- **Fragment Templates** (not listed): Many templates ending with `*Header.tpl`, `*Actions.tpl`, `*Blocks.tpl`, `*Contents.tpl`, etc. are intentionally not extending MainLayout because they are partial templates included by other views.

- **AJAX Partials**: Some views may be designed to be loaded via AJAX and intentionally don't have full page layout.

- **Modals/Popups**: Views that appear in modal dialogs don't need MainLayout.

- **Module-specific EditView templates** like `Users/EditView.tpl`, `Reservations/EditView.tpl`, etc. are often just fragment overrides that get included in `Base/EditView.tpl`, so they don't need to extend MainLayout themselves.

---

## Summary Statistics

- **Total templates using MainLayout**: 8
- **High-priority candidates**: ~10-15 views
- **Views that may intentionally not use MainLayout**: ~8-10 views
- **Fragment/partial templates**: Excluded from this analysis






