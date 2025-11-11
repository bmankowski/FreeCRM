# FreeCRM View Analyzer - Quick Start

## What Was Created

A Python script that analyzes your FreeCRM View classes and shows:

1. **Inheritance Tree** - Visual hierarchy of all View classes
2. **Viewer Assignments** - All `$viewer->assign()` calls from `preProcess` methods
3. **Cumulative View** - Shows which variables each class inherits from parents

## Quick Commands

### See everything (generates 1500+ lines):
```bash
python3 analyze_view_assignments.py > report.txt
```

### Analyze a specific class (e.g., ListView):
```bash
python3 analyze_view_assignments.py --class ListView --quiet
```

### See just the summary:
```bash
python3 analyze_view_assignments.py --summary-only
```

## What You'll Learn

- **Which viewer variables are available** in each View class
- **Where assignments come from** (which parent class sets each variable)
- **How inheritance works** in your View layer
- **What's available in templates** for each View type

## Example: Understanding ListView

Running `python3 analyze_view_assignments.py --class ListView --quiet` shows:

- ListView inherits from: Index → Basic → BaseViewController → BaseActionController
- It gets **35 viewer variables total**
- 20 from BaseViewController (PAGETITLE, BREADCRUMBS, STYLES, etc.)
- 14 from Basic (MODULE_NAME, MENUS, COMPANY_LOGO, etc.)
- 5 from ListView itself (CUSTOM_VIEWS, VIEWID, HEADER_LINKS, etc.)

## Files Created

1. **analyze_view_assignments.py** - The analyzer script
2. **view_analysis_report.txt** - Complete analysis of all 208 classes
3. **VIEW_ANALYZER_README.md** - Full documentation
4. **VIEW_ANALYZER_EXAMPLES.md** - Usage examples
5. **QUICK_START.md** - This file

## Stats

- **208 View classes** analyzed
- **20+ unique viewer variables** from BaseViewController
- **50+ total unique viewer variables** across all classes
- **Deepest inheritance**: 6 levels (BaseActionController → BaseViewController → Basic → Index → ListView → Rss\\ListView)

Enjoy analyzing your views! 📊
