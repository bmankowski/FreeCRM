# FreeCRM View Analyzer - Example Outputs

## Example 1: Query Detail Class

This shows what viewer variables are available in the Detail view:

```bash
python3 analyze_view_assignments.py --class Detail --quiet
```

Shows that Detail class inherits 31 viewer variables from its parent classes.

## Example 2: Compare Different ListView Implementations

```bash
python3 analyze_view_assignments.py --class ListView --quiet | grep "^Final Cumulative"
```

Shows how many cumulative assignments each ListView implementation has:
- Base ListView: 35 assignments
- RecycleBin ListView: 49 assignments (adds module list, search params, etc.)
- Portal ListView: 45 assignments (different pagination model)

## Example 3: Find Which Classes Set a Specific Variable

```bash
python3 analyze_view_assignments.py --summary-only | grep -A 3 "^MODULE:"
```

Shows all the different ways MODULE is set across different view classes.

## Example 4: Full Tree Analysis

```bash
python3 analyze_view_assignments.py > full_analysis.txt
```

Generates a complete 1500+ line report showing:
- Full inheritance tree starting from BaseActionController
- All 208 view classes analyzed
- Complete list of all viewer assignments
- Summary grouped by variable name

## Key Findings from Analysis

### Base Assignments (from BaseViewController)
Every view that extends BaseViewController gets these 20 base assignments:
- PAGETITLE, BREADCRUMBS, BREADCRUMB_TITLE, BREADCRUMBS_SEPARATOR
- HEADER_SCRIPTS, FOOTER_SCRIPTS, STYLES
- SKIN_PATH, LAYOUT_PATH, HTMLLANG, LANGUAGE, LANGUAGE_STRINGS
- SHOW_BODY_HEADER, USER_MODEL, MODULE, VIEW
- ACTIVITY_REMINDER, SCRIPT_TIME, PARENT_MODULE, ACTIVE_MODULES

### Additional from Basic Class
Views extending Basic add 14 more:
- CURRENTDATE, MODULE_NAME, QUALIFIED_MODULE
- MENUS, COMPANY_LOGO, HOME_MODULE_MODEL
- MENU_HEADER_LINKS, SEARCHABLE_MODULES, SEARCHED_MODULE
- CHAT_ACTIVE, REMINDER_ACTIVE

### ListView Specific
ListView classes add pagination and list-specific variables:
- LISTVIEW_ENTRIES, LISTVIEW_HEADERS, LISTVIEW_COUNT
- PAGING_MODEL, PAGE_NUMBER, ORDER_BY, SORT_ORDER
- CUSTOM_VIEWS, VIEWID, MODULE_MODEL
